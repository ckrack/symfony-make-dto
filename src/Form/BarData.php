<?php

namespace App\Form;

use App\Entity\Bar;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data transfer object for Bar.
 * Add your constraints as annotations to the properties.
 */
class BarData
{
    /**
     * @Assert\NotBlank(message="This value should not be blank.", payload=null)
     */
    public $name;

    /**
     * @Assert\NotBlank(message="This value should not be blank.", payload=null)
     */
    public $test;

    /**
     * Create DTO, optionally extracting data from a model.
     *
     * @param Bar|null $bar
     */
    public function __construct(? Bar $bar = null)
    {
        if ($bar instanceof Bar) {
            $this->extract($bar);
        }
    }

    /**
     * Fill entity with data from the DTO.
     *
     * @param Bar $bar
     */
    public function fill(Bar $bar)
    {
        $bar
            ->setName($this->name)
            ->setTest($this->test)
        ;

        return $bar;
    }

    /**
     * Extract data from entity into the DTO.
     *
     * @param Bar $bar
     */
    public function extract(Bar $bar): self
    {
        $this->name = $bar->getName();
        $this->test = $bar->getTest();

        return $this;
    }
}
