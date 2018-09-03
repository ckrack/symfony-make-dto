<?= "<?php\n" ?>
<?php use Symfony\Bundle\MakerBundle\Str; ?>

namespace <?= $namespace ?>;

<?php if (isset($bounded_full_class_name)): ?>
use <?= $bounded_full_class_name ?>;
<?php endif ?>

class <?= $class_name ?>

{
<?php if ($addHelpers): ?>
    /**
     * Create DTO, optionally extracting data from a model.
     *
     * @param <?= $bounded_class_name ?>|null $<?= lcfirst($bounded_class_name) ?>

     */
    public function __construct(? <?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>)
    {
        if ($<?= lcfirst($bounded_class_name) ?> instanceof <?= $bounded_class_name ?>) {
            $this->extract($<?= lcfirst($bounded_class_name) ?>);
        }
    }

    /**
     * Fill model with data from the DTO.
     *
     * @param <?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>

     */
    public function fill(<?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>)
    {
        $<?= lcfirst($bounded_class_name) ?>

<?php foreach($fields as $propertyName => $mapping): ?>
            ->set<?= Str::asCamelCase($propertyName) ?>($this->get<?= Str::asCamelCase($propertyName) ?>())
<?php endforeach; ?>
        ;
    }

    /**
     * Extract data from model into the DTO.
     *
     * @param <?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>

     */
    public function extract(<?= $bounded_class_name ?> $<?= $bounded_class_name ?>)
    {
<?php foreach($fields as $propertyName => $mapping): ?>
        $this->set<?= Str::asCamelCase($propertyName) ?>($<?= lcfirst($bounded_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>());
<?php endforeach; ?>
    }
<?php endif; ?>
}