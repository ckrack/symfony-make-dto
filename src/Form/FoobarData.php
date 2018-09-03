<?php

namespace App\Form;

use App\Entity\Foobar;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data transfer object for Foobar.
 * Add your constraints as annotations to the properties.
 */
class FoobarData
{
    private $name;

    /**
     * Create DTO, optionally extracting data from a model.
     *
     * @param Foobar|null $foobar
     */
    public function __construct(? Foobar $foobar = null)
    {
        if ($foobar instanceof Foobar) {
            $this->extract($foobar);
        }
    }

    /**
     * Fill model with data from the DTO.
     *
     * @param Foobar $foobar
     */
    public function fill(Foobar $foobar)
    {
        $foobar
            ->setName($this->getName())
        ;

        return $foobar;
    }

    /**
     * Extract data from model into the DTO.
     *
     * @param Foobar $foobar
     */
    public function extract(Foobar $foobar): self
    {
        $this->setName($foobar->getName());

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
}
