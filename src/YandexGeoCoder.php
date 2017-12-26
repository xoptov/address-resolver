<?php

namespace Xoptov\AddressResolver;

use Ds\Vector;
use GuzzleHttp\Client;
use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\Model\Coordinate;

class YandexGeoCoder implements GeoCoderInterface
{
	/** @var string */
	private $url;

	/** @var client */
	private $client;

	/** @var LocationManager */
	private $locationManager;

	/** @var Vector */
	private $buffer;

	/**
	 * YandexGeoCoder constructor.
	 * @param string $url
	 */
	public function __construct($url)
	{
		$this->url = $url;
		$this->client = new Client();
		$this->locationManager = new LocationManager();
		$this->buffer = new Vector();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNameByCoordinate(Coordinate $coordinate)
	{
		$result = $this->buffer->filter(function(Location $location) use ($coordinate) {
			$from = array(
				"type" => "Point",
				"coordinates" => array($coordinate->getLongitude(), $coordinate->getLatitude())
			);

			$to = array(
				"type" => "Point",
				"coordinates" => array($location->getLongitude(), $location->getLatitude())
			);

			if (vincenty($to, $from) / 1000 <= 0.5) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			/** @var Location $location */
			$location = $result->first();

			return $location->getName();
		}

		$options = array(
			"query" => array(
				"geocode" => sprintf("%f,%f", $coordinate->getLongitude(), $coordinate->getLatitude()),
				"format" => "json",
				"kind" => "house",
				"results" => 1
			)
		);

		$response = $this->client->get($this->url, $options);

		if ($response->getStatusCode() == 200) {
			$json = json_decode($response->getBody());
			$geoObjectCollection = $json->response->GeoObjectCollection;

			if (count($geoObjectCollection->featureMember)) {
				$featureMember = array_shift($geoObjectCollection->featureMember);

				if ($featureMember && isset($featureMember->GeoObject)) {
					$geoObject = $featureMember->GeoObject;

					if (isset($geoObject->metaDataProperty)) {
						$metaDataProperty = $geoObject->metaDataProperty;
						$location = $this->locationManager->create($metaDataProperty->GeocoderMetaData->text, $coordinate->getLatitude(), $coordinate->getLongitude());
						$this->buffer->push($location);

						return $location->getName();
					}
				}
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCoordinateByName($name)
	{
		$result = $this->buffer->filter(function(Location $location) use ($name) {
			if ($location->getName() === $name) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			/** @var Location $location */
			$location = $result->first();

			return $location->getCoordinate();
		}

		$options = array(
			"query" => array(
				"geocode" => $name,
				"format" => "json",
				"kind" => "locality",
				"results" => 1
			)
		);

		$response = $this->client->get($this->url, $options);

		if ($response->getStatusCode() == 200) {
			$json = json_decode($response->getBody());
			$geoObjectCollection = $json->response->GeoObjectCollection;

			if (count($geoObjectCollection->featureMember)) {
				$featureMember = array_shift($geoObjectCollection->featureMember);

				if ($featureMember) {
					$point = $featureMember->GeoObject->Point;
					list($longitude, $latitude) = explode(' ', $point->pos);
					$location = $this->locationManager->create($name, $latitude, $longitude);
					$this->buffer->push($location);

					return $location->getCoordinate();
				}
			}
		}

		return null;
	}
}