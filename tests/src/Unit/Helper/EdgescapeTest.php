<?php

namespace Drupal\Tests\akamai\Unit\Helper;

use Drupal\Tests\UnitTestCase;
use Drupal\akamai\Helper\Edgescape;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Edgescape Helper Tests.
 *
 * @group Akamai
 */
class EdgescapeTest extends UnitTestCase {

  /**
   * Get a mock config factory.
   */
  public function getConfigFactory($edgescapeEnabled = FALSE) {
    $config = ['edgescape_support' => $edgescapeEnabled];
    return $this->getConfigFactoryStub(['akamai.settings' => $config]);
  }

  /**
   * Get a mock request stack with request.
   */
  public function getRequestStack($headerValue = NULL) {
    $request = new Request();
    if ($headerValue) {
      $request->headers = new HeaderBag([Edgescape::EDGESCAPE_HEADER => (string) $headerValue]);
    }
    $requestStack = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
      ->disableOriginalConstructor()
      ->setMethods(['getCurrentRequest'])
      ->getMock();
    $requestStack->method('getCurrentRequest')
      ->willReturn($request);
    return $requestStack;
  }

  /**
   * Get an Edgescape header value.
   */
  public function getEdgescapeHeaderValue($info = []) {
    $info = $info + [
      'georegion' => '263',
      'country_code' => 'US',
      'region_code' => 'MA',
      'city' => 'CAMBRIDGE',
      'dma' => '506',
      'pmsa' => '1120',
      'areacode' => '617',
      'county' => 'MIDDLESEX',
      'fips' => '25017',
      'lat' => '42.3933',
      'long' => '-71.1333',
      'timezone' => 'EST',
      'zip' => '02138-02142',
      'continent' => 'NA',
      'throughput' => 'vhigh',
      'asnum' => '21399',
    ];
    return http_build_query($info, '', ',');
  }

  /**
   * Tests getEdgescapeHeaderValue creating a valid header value.
   */
  public function testGetEdgescapeHeaderValue() {
    $expected = 'georegion=263,country_code=US,region_code=MA,city=CAMBRIDGE,dma=506,pmsa=1120,areacode=617,county=MIDDLESEX,fips=25017,lat=42.3933,long=-71.1333,timezone=EST,zip=02138-02142,continent=NA,throughput=vhigh,asnum=21399';
    $this->assertSame($this->getEdgescapeHeaderValue(), $expected);
  }

  /**
   * Tests getInformationByType returns empty.
   */
  public function testGetInformationByTypeReturnsEmpty() {
    // Config false and header not present.
    $config = $this->getConfigFactory();
    $requestStack = $this->getRequestStack();
    $helper = new Edgescape($config, $requestStack);
    $this->assertSame($helper->getInformationByType('country_code'), '');

    // Config false and header present.
    $config = $this->getConfigFactory();
    $requestStack = $this->getRequestStack($this->getEdgescapeHeaderValue());
    $helper = new Edgescape($config, $requestStack);
    $this->assertSame($helper->getInformationByType('country_code'), '');

    // Config true and header not present.
    $config = $this->getConfigFactory(TRUE);
    $requestStack = $this->getRequestStack();
    $helper = new Edgescape($config, $requestStack);
    $this->assertSame($helper->getInformationByType('country_code'), '');
  }

  /**
   * Tests getInformationByType returns values.
   */
  public function testGetInformationByTypeReturnsValues() {
    // Config true and header present.
    $config = $this->getConfigFactory(TRUE);
    $requestStack = $this->getRequestStack($this->getEdgescapeHeaderValue());
    $helper = new Edgescape($config, $requestStack);
    $this->assertSame($helper->getInformationByType('country_code'), 'US');
    $this->assertSame($helper->getInformationByType('continent'), 'NA');
    $this->assertSame($helper->getInformationByType('foo'), '');
  }

}
