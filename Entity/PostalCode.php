<?php

namespace NS\DistanceBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="postalcodes")
 * @ORM\Entity(repositoryClass="NS\DistanceBundle\Repositories\PostalCode")
 */
class PostalCode
{
    /**
     *
     * @var integer $id
     * @ORM\Column(name="id",type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var string $postalCode
     * @ORM\Column(name="postal_code",type="string")
     */
    private $postalCode;

    /**
     * @var string $latitude
     * @ORM\Column(name="latitude",type="decimal", precision=14, scale=10)
     */
    private $latitude;

    /**
     * @var string $longitude
     * @ORM\Column(name="longitude",type="decimal", precision=14, scale=10)
     */
    private $longitude;
    
    /**
     * @var string $city
     * @ORM\Column(name="city",type="string")
     */
    private $city;
    
    /**
     * @var string $province
     * @ORM\Column(name="province",type="string")
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
        $this->postalCode = $postalCode;

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