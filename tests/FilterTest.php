<?php

class testfilter{
    public function __construct(\Illuminate\Container\Container $app){
        $this->app = $app;
    }

    public function apply($value){
        return strtolower($value);
    }
}

class FilterTest extends PHPUnit_Framework_TestCase{

    public function testAddedFiltersGetApplied()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());
        $filter->add('test.filter', function($value){
            return 'new value';
        }, 100, 'unique_id');
        $this->assertEquals('new value', $filter->apply('test.filter', 'original value'));
    }

    public function testAddedFiltersPriorityIsRespected()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());

        $filter->add('test2.filter', function($value){
            return 'newer value';
        }, 200, 'unique_id');

        $filter->add('test2.filter', function($value){
            return 'new value';
        }, 100, 'unique_id2');


        $this->assertEquals('newer value', $filter->apply('test2.filter', 'original value'));
    }

    public function testRemovalOfFilterByItsReference()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());

        $filter->add('test3.filter', function ($value) {
            return 'new value';
        }, 100, 'unique_id');

        $filter->add('test3.filter', function ($value) {
            return 'newer value';
        }, 200, 'unique_id2');

        $filter->remove('test3.filter', 'unique_id2');

        $this->assertEquals('new value', $filter->apply('test3.filter', 'original value'));
    }

    public function testFilterResultsAppliedToArray()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());

        $filter->add('test4.filter', function ($value) {
            $value[0] = 'new value';
            return $value;
        }, 100, 'unique_id');

        $this->assertEquals(json_encode(['new value', false, 10]), json_encode($filter->apply('test4.filter', ['original value', false, 10])));

    }

    public function testClassReferenceAdditionsInsteadOfClosures()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());

        $filter->add('test5.filter', 'testfilter@apply', 100, 'unique_id');

        $this->assertEquals('runthroughclass', $filter->apply('test5.filter', 'runThroughClass'));
    }

    public function testRemovalOfClassReferenceAdditionsWithoutProvidingRef()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());

        $filter->add('test6.filter', 'testfilter@apply');

        $filter->remove('test6.filter', 'testfilter@apply');

        $this->assertEquals('run_without_filters', $filter->apply('test5.filter', 'run_without_filters'));
    }

    public function testAddMethodWithArrayOfNames()
    {
        $filter = new \LeeMason\Filter\Dispatcher(new \Illuminate\Container\Container());

        $filter->add(['test7.filter', 'test8.filter'], function($value){
            return 'new value';
        });

        $this->assertEquals('new value', $filter->apply('test7.filter', 'old value'));
        $this->assertEquals('new value', $filter->apply('test8.filter', 'old value'));
    }
}