<?php
# this, except this comment is auto-generated code by the maker.
# make:dto Foobar foobar / yes / yes (id omitted)
namespace App\Form;

use App\Entity\Foobar;

class FoobarData
{
    private $name;

    /**
     * Create DTO, optionally extracting data from a model.
     *
     * @param Foobar|null $foobar
     */
    public function __construct(? Foobar $foobar)
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
    }

    /**
     * Extract data from model into the DTO.
     *
     * @param Foobar $foobar
     */
    public function extract(Foobar $Foobar)
    {
        $this->setName($foobar->getName());
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
