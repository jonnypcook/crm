<?php
namespace Application\Service;

class ExampleDoctrineService extends AbstractDoctrine
{
    protected $objectManager;
    
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }
}