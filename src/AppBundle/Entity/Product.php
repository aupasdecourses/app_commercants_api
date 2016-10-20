<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductRepository")
 */
class Product
{
    /**
     * Autoincrement ID
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Unique SKU of the product
     *
     * @var string
     *
     * @ORM\Column(name="sku", type="string", length=255)
     */
    private $sku;

    /**
     * Merchant reference
     *
     * @var string
     *
     * @ORM\Column(name="ref", type="string", length=255)
     */
    private $ref;

    /**
     * Name of the product
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="The name cannot be empty")
     */
    private $name;

    /**
     * Avalaible on APDC
     *
     * @var boolean
     *
     * @ORM\Column(name="available", type="boolean")
     */
    private $available;

    /**
     * Slected on APDC
     *
     * @var boolean
     *
     * @ORM\Column(name="selected", type="boolean")
     */
    private $selected;

    /**
     * Price
     *
     * @var float
     *
     * @ORM\Column(name="price", type="float", length=255)
     */
    private $price;

    /**
     * Type of price (per kg, per unit)
     *
     * @var string
     *
     * @ORM\Column(name="price_unit", type="integer", length=11)
     */
    private $priceUnit;

    /**
     * A short description of the product
     *
     * @var string
     *
     * @ORM\Column(name="short_description", type="string", length=255)
     */
    private $shortDescription;

    /**
     * Wieght of a portion (in Kg)
     *
     * @var string
     *
     * @ORM\Column(name="portion_weight", type="float", length=255)
     */
    private $portionWeight;

    /**
     * Number of portion
     *
     * @var string
     *
     * @ORM\Column(name="portion_number", type="integer", length=11)
     */
    private $portionNumber;

    /**
     * VAT
     *
     * @var string
     *
     * @ORM\Column(name="tax", type="integer", length=11)
     */
    private $tax;

    /**
     * Origin of the product
     *
     * @var string
     *
     * @ORM\Column(name="origin", type="string", length=255)
     */
    private $origin;

    /**
     * Bio product
     *
     * @var string
     *
     * @ORM\Column(name="bio", type="boolean")
     */
    private $bio;

    /**
     * The merchant user
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sku
     *
     * @param string $sku
     *
     * @return Product
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set ref
     *
     * @param string $ref
     *
     * @return Product
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref
     *
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set available
     *
     * @param boolean $available
     *
     * @return Product
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * Get available
     *
     * @return boolean
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * Set selected
     *
     * @param boolean $selected
     *
     * @return Product
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * Get selected
     *
     * @return boolean
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Product
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set priceUnit
     *
     * @param float $priceUnit
     *
     * @return Product
     */
    public function setPriceUnit($priceUnit)
    {
        $this->priceUnit = $priceUnit;

        return $this;
    }

    /**
     * Get priceUnit
     *
     * @return float
     */
    public function getPriceUnit()
    {
        return $this->priceUnit;
    }

    /**
     * Set shortDescription
     *
     * @param string $shortDescription
     *
     * @return Product
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Set portionWeight
     *
     * @param float $portionWeight
     *
     * @return Product
     */
    public function setPortionWeight($portionWeight)
    {
        $this->portionWeight = $portionWeight;

        return $this;
    }

    /**
     * Get portionWeight
     *
     * @return float
     */
    public function getPortionWeight()
    {
        return $this->portionWeight;
    }

    /**
     * Set portionNumber
     *
     * @param integer $portionNumber
     *
     * @return Product
     */
    public function setPortionNumber($portionNumber)
    {
        $this->portionNumber = $portionNumber;

        return $this;
    }

    /**
     * Get portionNumber
     *
     * @return integer
     */
    public function getPortionNumber()
    {
        return $this->portionNumber;
    }

    /**
     * Set tax
     *
     * @param integer $tax
     *
     * @return Product
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * Get tax
     *
     * @return integer
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Set origin
     *
     * @param string $origin
     *
     * @return Product
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * Get origin
     *
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set bio
     *
     * @param boolean $bio
     *
     * @return Product
     */
    public function setBio($bio)
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * Get bio
     *
     * @return boolean
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Product
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
