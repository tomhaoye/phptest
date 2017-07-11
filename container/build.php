<?php
spl_autoload_register('autoload');

function autoload($class)
{
    require __Dir__ . '/' . str_replace('\\', '/', $class) . '.php';
}

$container = new Container();

$container->bind('car', function ($container, $power) {
    return new Car($container->get($power));
});

$container->bind('two', function ($container) {
    return new TwoPower;
});

$container->bind('four', function ($container) {
    return new FourPower();
});

$car1 = $container->get('car', ['two']);
$car2 = $container->get('car', ['four']);
