<?php

namespace NS\DistanceBundle\Entity;

class PostalCode implements GeographicPointInterface
{
    /**
     * @var integer $id
     */
    private $id;
    
    /**
     * @var string $postalCode
     */
    private $postalCode;

    /**
     * @var string $latitude
     */
    private $latitude;

    /**
     * @var string $longitude
     */
    private $longitude;
    
    /**
     * @var string $city
     */
    private $city;
    
    /**
     * @var string $province
     */
    private $province;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPostalCode() 
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return \NS\DistanceBundle\Entity\PostalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = strtoupper(preg_replace('/\s+/', '', $postalCode));

        return $this;
    }

    /**
     * @return double
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param double $latitude
     * @return \NS\DistanceBundle\Entity\PostalCode
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return double
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param double $longitude
     * @return \NS\DistanceBundle\Entity\PostalCode
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return \NS\DistanceBundle\Entity\PostalCode
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param string $province
     * @return \NS\DistanceBundle\Entity\PostalCode
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }
}

