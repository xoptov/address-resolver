<?php

namespace Xoptov\AddressResolver;

use Ds\Map;
use GuzzleHttp\ClientInterface;
use Respect\Validation\Validator;
use Xoptov\AddressResolver\Model\Region;
use Xoptov\AddressResolver\Model\Locality;
use Xoptov\AddressResolver\Model\Coordinate;

class DaData
{
	/** @var ClientInterface */
	private $client;

	/** @var string */
	private $url;

	/** @var string */
	private $apiKey;

	/** @var string */
	private $secretKey;

	/** @var Map */
	private $buffer;

	/**
	 * @param ClientInterface $client
	 * @param string $url
	 * @param string $apiKey
	 * @param string $secretKey
	 */
	public function __construct(ClientInterface $client, $url, $apiKey, $secretKey)
	{
		$this->client = $client;
		$this->url = $url;
		$this->apiKey = $apiKey;
		$this->secretKey = $secretKey;
		$this->buffer = new Map();
	}

	/**
	 * @param string $address
	 * @return \StdClass
	 */
	public function getAddressObject($address)
	{
		if ($this->buffer->hasKey($address)) {
			return $this->buffer->get($address);
		}

		$options = array(
			"headers" => array(
				"Content-Type" => "application/json",
				"Authorization" => sprintf("Token %s", $this->apiKey),
				"X-Secret" => $this->secretKey
			),
			"body" => sprintf("[\"%s\"]", $address)
		);

		$response = $this->client->post($this->url, $options);

		if ($response->getStatusCode() == 200) {
			$data = json_decode($response->getBody());

			if (is_array($data) && count($data)) {
				$addressObject = array_shift($data);
				$this->buffer->put($address, $addressObject);

				return $addressObject;
			}
		}

		return null;
	}

	/**
	 * @param RegionManager $manager
	 * @param \StdClass $addressObject
	 * @return Region
	 */
	public function createRegion(RegionManager $manager, \StdClass $addressObject)
	{
		$validator = Validator::create();

		$validator->addRules(array(
			Validator::attribute("region", Validator::notBlank()),
			Validator::attribute("region_type_full", Validator::notBlank()),
			Validator::attribute("region_fias_id", Validator::notBlank())
		));

		$validator->check($addressObject);

		return $manager->create($addressObject->region_fias_id, $addressObject->region, $addressObject->region_type_full, true);
	}

	/**
	 * @param LocalityManager $manager
	 * @param \StdClass $addressObject
	 * @param Region $region
	 * @param Coordinate $coordinate
	 * @return Locality
	 */
	public function createLocality(LocalityManager $manager, \StdClass $addressObject, Region $region, Coordinate $coordinate)
	{
		$validator = Validator::create();

		$validator->addRules(array(
			Validator::attribute("city", Validator::notBlank()),
			Validator::attribute("city_type_full", Validator::notBlank()),
			Validator::attribute("city_fias_id", Validator::notBlank())
		));

		if ($validator->validate($addressObject)) {
			return $manager->create($addressObject->city_fias_id, $addressObject->city, $addressObject->city_type_full, $region, $coordinate, true);
		}

		unset($validator);

		$validator = Validator::create();
		$validator->addRules(array(
			Validator::attribute("settlement", Validator::notBlank()),
			Validator::attribute("settlement_type_full", Validator::notBlank()),
			Validator::attribute("settlement_fias_id", Validator::notBlank())
		));

		$validator->check($addressObject);

		return $manager->create($addressObject->settlement_fias_id, $addressObject->settlement, $addressObject->settlement_type_full, $region, $coordinate, true);
	}
}