<?php
namespace Payum\Bundle\PayumBundle\Tests\Controller;

use Payum\Bundle\PayumBundle\Controller\PayoutController;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Token;
use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Request\Payout;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\HttpRequestVerifierInterface;
use Payum\Core\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

class PayoutControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfController()
    {
        $rc = new \ReflectionClass(PayoutController::class);

        $this->assertTrue($rc->isSubclassOf(Controller::class));
    }

    /**
     * @test
     */
    public function shouldExecutePayoutRequest()
    {
        $request = Request::create('/');
        $request->query->set('foo', 'fooVal');

        $token = new Token;
        $token->setGatewayName('theGateway');
        $token->setAfterUrl('http://example.com/theAfterUrl');

        $httpRequestVerifierMock = $this->getMock(HttpRequestVerifierInterface::class);
        $httpRequestVerifierMock
            ->expects($this->once())
            ->method('verify')
            ->with($this->identicalTo($request))
            ->will($this->returnValue($token))
        ;
        $httpRequestVerifierMock
            ->expects($this->once())
            ->method('invalidate')
            ->with($this->identicalTo($token))
        ;

        $gatewayMock = $this->getMock(GatewayInterface::class);
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(Payout::class))
        ;

        $registryMock = $this->getMock(RegistryInterface::class);
        $registryMock
            ->expects($this->once())
            ->method('getGateway')
            ->with('theGateway')
            ->will($this->returnValue($gatewayMock))
        ;

        $payum = new Payum(
            $registryMock,
            $httpRequestVerifierMock,
            $this->getMock(GenericTokenFactoryInterface::class),
            $this->getMock(StorageInterface::class)
        );

        $container = new Container;
        $container->set('payum', $payum);

        $controller = new PayoutController;
        $controller->setContainer($container);

        $response = $controller->doAction($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('http://example.com/theAfterUrl', $response->getTargetUrl());
    }
}
