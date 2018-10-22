<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Validation;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\FileManager;
use App\Maker\DTOClassSourceManipulator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Constraint;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Clemens Krack <info@clemenskrack.com>
 */
final class MakeDTO extends AbstractMaker
{
    private $entityHelper;
    private $fileManager;

    public function __construct(
        DoctrineHelper $entityHelper,
        FileManager $fileManager
    ) {
        $this->entityHelper = $entityHelper;
        $this->fileManager = $fileManager;
    }

    public static function getCommandName(): string
    {
        return 'make:dto';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new DTO class')
            ->addArgument('name', InputArgument::REQUIRED, sprintf('The name of the DTO class (e.g. <fg=yellow>%sData</>)', Str::asClassName(Str::getRandomTerm())))
            ->addArgument('bound-class', InputArgument::REQUIRED, 'The name of Entity that the DTO will be bound to')
            ->setHelp(file_get_contents(__DIR__.'/../Resources'.'/help/MakeDTO.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('bound-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('bound-class')) {
            $argument = $command->getDefinition()->getArgument('bound-class');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(function ($answer) use ($entities) {return Validator::existsOrNull($answer, $entities); });
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('bound-class', $io->askQuestion($question));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $formClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Form\\',
            'Data'
        );

        $boundClass = $input->getArgument('bound-class');

        $boundClassDetails = $generator->createClassNameDetails(
            $boundClass,
            'Entity\\'
        );

        // get some doctrine details
        $doctrineEntityDetails = $this->entityHelper->createDoctrineDetails($boundClassDetails->getFullName());

        // get class metadata (used by regenerate)
        $metaData = $this->entityHelper->getMetadata($boundClassDetails->getFullName());

        // list of fields
        $fields = $metaData->fieldMappings;

        if (null !== $doctrineEntityDetails) {
            $formFields = $doctrineEntityDetails->getFormFields();
        }

        $boundClassVars = [
            'bounded_full_class_name' => $boundClassDetails->getFullName(),
            'bounded_class_name' => $boundClassDetails->getShortName(),
        ];

        // the result is passed to the template
        $addHelpers = $io->confirm('Add helper extract/fill methods?');

        // filter id from fields?
        $omitId = $io->confirm('Omit Id field in DTO?');

        if ($omitId) {
            $fields = array_filter($fields, function ($field) {
                // mapping includes id field when property is an id
                if (!empty($field['id'])) {
                    return false;
                }

                return true;
            });
        }

        // Skeleton?

        $DTOClassPath = $generator->generateClass(
            $formClassNameDetails->getFullName(),
            __DIR__.'/../Resources'.'/skeleton/form/Data.tpl.php',
            array_merge(
                [
                    'fields' => $fields,
                    'addHelpers' => $addHelpers,
                ],
                $boundClassVars
            )
        );

        $generator->writeChanges();

        $manipulator = $this->createClassManipulator($DTOClassPath);

        $mappedFields = $this->getMappedFieldsInEntity($metaData);

        foreach ($fields as $fieldName => $mapping) {
            if (!\in_array($fieldName, $mappedFields)) {
                continue;
            }

            $annotationReader = new AnnotationReader();

            // Lookup classname for inherited properties
            if (array_key_exists('declared', $mapping)) {
                $fullClassName = $mapping['declared'];
            } else {
                $fullClassName = $boundClassDetails->getFullName();
            }

            // Property Annotations
            $reflectionProperty = new \ReflectionProperty($fullClassName, $fieldName);
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

            $comments = [];

            foreach ($propertyAnnotations as $annotation) {
                // we want to copy the asserts, so look for their interface
                if($annotation instanceof Constraint) {
                    $comments[] = $manipulator->buildAnnotationLine('@Assert\\'.(new \ReflectionClass($annotation))->getShortName(), (array) $annotation);
                }
            }

            $manipulator->addEntityField($fieldName, $mapping, $comments);

        }

        $this->fileManager->dumpFile(
            $DTOClassPath,
            $manipulator->getSourceCode()
        );

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Create your form with this DTO and start using it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Validation::class,
            'validator',
            // add as an optional dependency: the user *probably* wants validation
            false
        );
    }

    private function createClassManipulator(string $classPath): DTOClassSourceManipulator
    {
        return new DTOClassSourceManipulator(
            $this->fileManager->getFileContents($classPath),
            // overwrite existing methods
            true,
            // use annotations
            true
        );
    }

    private function getMappedFieldsInEntity(ClassMetadata $classMetadata)
    {
        /* @var $classReflection \ReflectionClass */
        $classReflection = $classMetadata->reflClass;

        $targetFields = array_merge(
            array_keys($classMetadata->fieldMappings),
            array_keys($classMetadata->associationMappings)
        );

        return $targetFields;
    }
}
