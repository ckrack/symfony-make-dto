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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Validation;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Clemens Krack <info@clemenskrack.com>
 */
final class MakeDTO extends AbstractMaker
{
    private $entityHelper;

    public function __construct(
        DoctrineHelper $entityHelper,
        FileManager $fileManager,
        Generator $generator = null
    ) {
        $this->entityHelper = $entityHelper;
        $this->fileManager = $fileManager;
        //$this->generator = $generator;
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
            //->addArgument('add-methods', InputArgument::OPTIONAL, 'Add fill/extract helper methods?', true)
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDTO.txt'))
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

        $DTOClassPath = $generator->generateClass(
            $formClassNameDetails->getFullName(),
            // @ TODO change filename to relative
            __DIR__.'/../Resources/skeleton/form/Data.tpl.php',
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

            $manipulator->addEntityField($fieldName, $mapping);
        }

        $this->fileManager->dumpFile(
            $DTOClassPath,
            $manipulator->getSourceCode()
        );

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Add fields to your form and start using it.',
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

    private function createClassManipulator(string $classPath): ClassSourceManipulator
    {
        return new ClassSourceManipulator(
            $this->fileManager->getFileContents($classPath),
            // do not overwrite existing methods
            false,
            // use annotations
            // if properties need to be generated then, by definition,
            // some non-annotation config is being used, and so, the
            // properties should not have annotations added to them
            false
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

        if ($classReflection) {
            // exclude traits
            $traitProperties = [];

            foreach ($classReflection->getTraits() as $trait) {
                foreach ($trait->getProperties() as $property) {
                    $traitProperties[] = $property->getName();
                }
            }

            $targetFields = array_diff($targetFields, $traitProperties);

            // exclude inherited properties
            $targetFields = array_filter($targetFields, function ($field) use ($classReflection) {
                return $classReflection->hasProperty($field) &&
                    $classReflection->getProperty($field)->getDeclaringClass()->getName() == $classReflection->getName();
            });
        }

        return $targetFields;
    }
}
