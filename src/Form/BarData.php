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
    private $name;

    /**
     * @Assert\NotBlank(message="This value should not be blank.", payload=null)
     */
    private $test;

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
     * Fill model with data from the DTO.
     *
     * @param Bar $bar
     */
    public function fill(Bar $bar)
    {
        $bar
            ->setName($this->getName())
            ->setTest($this->getTest())
        ;

        return $bar;
    }

    /**
     * Extract data from model into the DTO.
     *
     * @param Bar $bar
     */
    public function extract(Bar $bar): self
    {
        $this->setName($bar->getName());
        $this->setTest($bar->getTest());

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTest(): ?string
    {
        return $this->test;
    }

    public function setTest(string $test): self
    {
        $this->test = $test;

        return $this;
    }
}
