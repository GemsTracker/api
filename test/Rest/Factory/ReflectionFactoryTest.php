<?php


namespace GemsTest\Rest\Factory;


use Gems\Rest\Factory\ReflectionFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zalt\Loader\ProjectOverloader;

class ReflectionFactoryTest extends TestCase
{
    public function testInvokeWithoutContstructor()
    {
        $factory = new ReflectionFactory();

        $container = $this->getContainer([EmptyClass::class => EmptyClass::class]);

        $newClass = $factory->__invoke($container, EmptyClass::class);

        $this->assertInstanceOf(EmptyClass::class, $newClass, 'Correct class not found');
    }

    public function testInvokeInterface()
    {
        $factory = new ReflectionFactory();

        $container = $this->getContainer([EmptyInterface::class => EmptyInterface::class]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Target class ' . EmptyInterface::class . ' is not instantiable.');
        $factory->__invoke($container, EmptyInterface::class);
    }

    public function testConstructorClass()
    {
        $factory = new ReflectionFactory();

        $container = $this->getContainer([ConstructorClass::class => ConstructorClass::class], [EmptyClass::class]);

        $newClass = $factory->__invoke($container, ConstructorClass::class);

        $this->assertInstanceOf(ConstructorClass::class, $newClass, 'Correct class not found');
        $this->assertInstanceOf(EmptyClass::class, $newClass->emptyClass, 'Correct added Class not found');
    }

    public function testDefaultValueClass()
    {
        $factory = new ReflectionFactory();

        $container = $this->getContainer([DefaultValueClass::class => DefaultValueClass::class]);

        $newClass = $factory->__invoke($container, DefaultValueClass::class);

        $this->assertInstanceOf(DefaultValueClass::class, $newClass, 'Correct class not found');
        $this->assertEquals('This should be the default value', $newClass->defaultVariable);
    }

    public function testNoDefaultValueClass()
    {
        $factory = new ReflectionFactory();

        $container = $this->getContainer([NoDefaultValueClass::class => NoDefaultValueClass::class]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Dependency [default] can't be resolved in class " . NoDefaultValueClass::class);
        $factory->__invoke($container, NoDefaultValueClass::class);
    }

    public function testNoKnownClassConstructorClass()
    {
        $factory = new ReflectionFactory();

        $container = $this->getContainer([ConstructorClass::class => ConstructorClass::class]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Dependency [empty] can't be resolved in class " . ConstructorClass::class);
        $factory->__invoke($container, ConstructorClass::class);
    }

    private function getContainer($loaderFindClasses = [], $containerClasses = [])
    {
        $loaderProphesy    = $this->prophesize(ProjectOverloader::class);

        foreach($loaderFindClasses as $find=>$return) {
            $loaderProphesy->find($find)->willReturn($return);

            $loaderProphesy->create(Argument::type('string'), Argument::any())->will(function($args) {
                $className = array_shift($args);
                $newArgs = $args;
                return new $className(...$newArgs);
            });
        }

        $containerProphesy = $this->prophesize(ContainerInterface::class);
        $containerProphesy->get('loader')->willReturn($loaderProphesy->reveal());

        foreach($containerClasses as $className) {
            $containerProphesy->has($className)->willReturn(true);
            $containerProphesy->get($className)->willReturn(new $className);
        }

        $containerProphesy->has(Argument::any())->willReturn(false);

        return $containerProphesy->reveal();
    }
}