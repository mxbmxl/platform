<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ActionGroupTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
    protected $actionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
    protected $conditionFactory;

    /** @var ActionGroup */
    protected $actionGroup;

    /** @var ActionData */
    protected $data;

    protected function setUp()
    {
        $this->actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionGroup = new ActionGroup(
            $this->actionFactory,
            $this->conditionFactory,
            new ActionGroupDefinition()
        );

        $this->data = new ActionData();
    }

    protected function tearDown()
    {
        unset($this->actionFactory, $this->conditionFactory);
    }

    /**
     * @param ActionData $data
     * @param ActionInterface $action
     * @param ConfigurableCondition $condition
     * @param string $actionName
     * @param string $exceptionMessage
     *
     * @dataProvider executeProvider
     */
    public function testExecute(
        ActionData $data,
        ActionInterface $action,
        ConfigurableCondition $condition,
        $actionName,
        $exceptionMessage = ''
    ) {
        $this->actionGroup->getDefinition()->setName($actionName);
        $this->actionGroup->getDefinition()->setActions(['action1']);
        $this->actionGroup->getDefinition()->setConditions(['condition1']);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->willReturn($action);

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($condition);

        $errors = new ArrayCollection();

        if ($exceptionMessage) {
            $this->setExpectedException(
                'Oro\Bundle\ActionBundle\Exception\ForbiddenActionException',
                $exceptionMessage
            );
        }

        $this->actionGroup->execute($data, $errors);

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        $data = new ActionData();

        return [
            '!isConditionAllowed' => [
                'data' => $data,
                'action' => $this->createAction($this->never(), $data),
                'condition' => $this->createCondition($this->once(), $data, false),
                'actionName' => 'TestName2',
                'exception' => 'ActionGroup "TestName2" is not allowed.'
            ],
            'isAllowed' => [
                'data' => $data,
                'action' => $this->createAction($this->once(), $data),
                'condition' => $this->createCondition($this->once(), $data, true),
                'actionName' => 'TestName3',
            ],
        ];
    }

    /**
     *
     * @dataProvider isAllowedProvider
     * @param ActionData $data
     * @param ConfigurableCondition $condition
     * @param bool $allowed
     */
    public function testIsAllowed(ActionData $data, $condition, $allowed)
    {
        if ($condition) {
            $this->actionGroup->getDefinition()->setConditions(['condition1']);
        }

        $this->conditionFactory->expects($condition ? $this->once() : $this->never())
            ->method('create')
            ->willReturn($condition);

        $this->assertEquals($allowed, $this->actionGroup->isAllowed($data));
    }

    /**
     * @return array
     */
    public function isAllowedProvider()
    {
        $data = new ActionData();

        return [
            'no conditions' => [
                'data' => $data,
                'condition' => null,
                'allowed' => true,
            ],
            '!isConditionAllowed' => [
                'data' => $data,
                'condition' => $this->createCondition($this->once(), $data, false),
                'allowed' => false,
            ],
            'allowed' => [
                'data' => $data,
                'condition' => $this->createCondition($this->once(), $data, true),
                'allowed' => true,
            ],
        ];
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionData $data
     * @return ActionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAction(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionData $data
    ) {
        /* @var $action ActionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $action->expects($expects)
            ->method('execute')
            ->with($data);

        return $action;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionData $data
     * @param bool $returnValue
     * @return ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCondition(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionData $data,
        $returnValue
    ) {
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Component\Action\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($expects)
            ->method('evaluate')
            ->with($data)
            ->willReturn($returnValue);

        return $condition;
    }
}
