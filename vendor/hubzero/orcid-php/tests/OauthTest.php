<?php
/**
 * @package   orcid-php
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 */

use Orcid\Oauth;
use \Mockery as m;

/**
 * Base ORCID oauth tests
 */
class OauthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Gets a sample oauth object
     *
     * @return  object
     **/
    public function oauth()
    {
        $http = m::mock('Orcid\Http\Curl');

        return new Oauth($http);
    }

    /**
     * Test to make sure we can get a basic authorization url
     *
     * @return  void
     **/
    public function testGetBasicAuthorizationUrl()
    {
        $oauth = $this->oauth()
                      ->useProductionEnvironment()
                      ->setClientId('1234')
                      ->setScope('/authorize')
                      ->setRedirectUri('here');

        $this->assertEquals(
            'https://orcid.org/oauth/authorize?client_id=1234&scope=/authorize&redirect_uri=here&response_type=code',
            $oauth->getAuthorizationUrl(),
            'Failed to fetch a properly formatted authorization URL'
        );

        $oauth->useMembersApi();
        $this->assertEquals(
            'https://orcid.org/oauth/authorize?client_id=1234&scope=/authorize&redirect_uri=here&response_type=code',
            $oauth->getAuthorizationUrl(),
            'Failed to fetch a properly formatted authorization URL'
        );
    }

    /**
     * Test to make sure we can get a basic authorization url for the sandbox environment
     *
     * @return  void
     **/
    public function testGetBasicSandboxAuthorizationUrl()
    {
        $oauth = $this->oauth()
                      ->useSandboxEnvironment()
                      ->setClientId('1234')
                      ->setScope('/authorize')
                      ->setRedirectUri('here');

        $this->assertEquals(
            'https://sandbox.orcid.org/oauth/authorize?client_id=1234&scope=/authorize&redirect_uri=here&response_type=code',
            $oauth->getAuthorizationUrl(),
            'Failed to fetch a properly formatted authorization URL'
        );

        $oauth->useMembersApi();
        $this->assertEquals(
            'https://sandbox.orcid.org/oauth/authorize?client_id=1234&scope=/authorize&redirect_uri=here&response_type=code',
            $oauth->getAuthorizationUrl(),
            'Failed to fetch a properly formatted authorization URL'
        );
    }

    /**
     * Test to make sure we throw an exception for a missing client id
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testGetAuthorizationUrlThrowsExceptionForMissingClientId()
    {
        $oauth = $this->oauth()
                      ->setScope('/authorize')
                      ->setRedirectUri('here')
                      ->getAuthorizationUrl();
    }

    /**
     * Test to make sure we throw an exception for a missing scope
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testGetAuthorizationUrlThrowsExceptionForMissingScope()
    {
        $oauth = $this->oauth()
                      ->setClientId('1234')
                      ->setRedirectUri('here')
                      ->getAuthorizationUrl();
    }

    /**
     * Test to make sure we throw an exception for a missing redirect uri
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testGetAuthorizationUrlThrowsExceptionForMissingRedirectUri()
    {
        $oauth = $this->oauth()
                      ->setClientId('1234')
                      ->setScope('/authorize')
                      ->getAuthorizationUrl();
    }

    /**
     * Test to make sure we can get an authorization url with the showLogin option enabled
     *
     * @return  void
     **/
    public function testGetAuthorizationUrlHasAdditionalParameter()
    {
        $url = $this->oauth()
                    ->setClientId('1234')
                    ->setScope('/authorize')
                    ->setRedirectUri('here')
                    ->showLogin()
                    ->setState('foobar')
                    ->setFamilyNames('Smith')
                    ->setGivenNames('John')
                    ->setEmail('me@gmail.com')
                    ->getAuthorizationUrl();

        $this->assertRegExp('/&show_login=true/', $url, 'Failed to fetch an authorization URL with the show_login parameters set');
        $this->assertRegExp('/&state=foobar/', $url, 'Failed to fetch an authorization URL with the state parameters set');
        $this->assertRegExp('/&family_names=Smith/', $url, 'Failed to fetch an authorization URL with the family_names parameters set');
        $this->assertRegExp('/&given_names=John/', $url, 'Failed to fetch an authorization URL with the given_names parameters set');
        $this->assertRegExp('/&email=me%40gmail.com/', $url, 'Failed to fetch an authorization URL with the email parameters set');
    }

    /**
     * Test to make sure an invalid code causes an exception when authenticating
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testAuthenticateThrowsExceptionForInvalidCode()
    {
        $this->oauth()->authenticate('1234567');
    }

    /**
     * Test to make sure we throw an exception for a missing client id
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testAuthenticateThrowsExceptionForMissingClientId()
    {
        $oauth = $this->oauth()
                      ->setClientSecret('12345')
                      ->setRedirectUri('here')
                      ->authenticate('123456');
    }

    /**
     * Test to make sure we throw an exception for a missing client secret
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testAuthenticateThrowsExceptionForMissingClientSecret()
    {
        $oauth = $this->oauth()
                      ->setClientId('1234')
                      ->setRedirectUri('here')
                      ->authenticate('123456');
    }

    /**
     * Test to make sure we throw an exception for a missing redirect uri
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testAuthenticateThrowsExceptionForMissingRedirectUri()
    {
        $oauth = $this->oauth()
                      ->setClientId('1234')
                      ->setClientSecret('12345')
                      ->authenticate('123456');
    }

    /**
     * Test to make sure we throw an exception for a bad oauth response
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testAuthenticateThrowsExceptionForFailedRequest()
    {
        // Overload some curl methods to simple return self
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('setPostFields')
             ->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')
             ->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setHeader')
             ->andReturn(m::self());

        $response = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'response-failure.json');
        $http->shouldReceive('execute')->andReturn($response);

        $oauth = new Oauth($http);

        $oauth->setClientId('1234')
              ->setClientSecret('12345')
              ->setRedirectUri('here')
              ->authenticate('123456');
    }

    /**
     * Test to make sure a valid response sets the access token and orcid
     *
     * @return  void
     **/
    public function testAuthenticateSetsPropertiesOnValidResponse()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('setPostFields', 'setUrl', 'setHeader')->andReturn(m::self());

        // Tell the curl method to return an empty ORCID iD
        $response = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'response-success.json');
        $http->shouldReceive('execute')->andReturn($response);

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->shouldReceive('setAccessToken')->once()->with('123456789');
        $oauth->shouldReceive('setOrcid')->once()->with('0000-0000-0000-0000');

        $oauth->setClientId('1234')
              ->setClientSecret('12345')
              ->setRedirectUri('here')
              ->authenticate('123456');
    }

    /**
     * Test to make sure no access token results in not authenticated
     *
     * @return  void
     **/
    public function testIsAuthenticatedFailsWithNoAccessToken()
    {
        $this->assertFalse($this->oauth()->isAuthenticated(), 'The oauth object failed to report that it was unauthenticated.');
    }

    /**
     * Test to make sure we build the appropriate production url for public api token requests
     *
     * @return  void
     **/
    public function testBuildPublicProductionTokenUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('setPostFields', 'setHeader')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://pub.orcid.org/oauth/token')->andReturn(m::self());

        // Tell the curl method to return an empty ORCID iD
        $response = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'response-success.json');
        $http->shouldReceive('execute')->andReturn($response);

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useProductionEnvironment()
              ->setClientId('1234')
              ->setClientSecret('12345')
              ->setRedirectUri('here')
              ->authenticate('123456');
    }

    /**
     * Test to make sure we build the appropriate production url for member api token requests
     *
     * @return  void
     **/
    public function testBuildMemberProductionTokenUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('setPostFields', 'setHeader')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://api.orcid.org/oauth/token')->andReturn(m::self());

        // Tell the curl method to return an empty ORCID iD
        $response = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'response-success.json');
        $http->shouldReceive('execute')->andReturn($response);

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useProductionEnvironment()
              ->useMembersApi()
              ->setClientId('1234')
              ->setClientSecret('12345')
              ->setRedirectUri('here')
              ->authenticate('123456');
    }

    /**
     * Test to make sure we build the appropriate sandbox url for public api token requests
     *
     * @return  void
     **/
    public function testBuildPublicSandboxTokenUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('setPostFields', 'setHeader')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://pub.sandbox.orcid.org/oauth/token')->andReturn(m::self());

        // Tell the curl method to return an empty ORCID iD
        $response = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'response-success.json');
        $http->shouldReceive('execute')->andReturn($response);

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useSandboxEnvironment()
              ->setClientId('1234')
              ->setClientSecret('12345')
              ->setRedirectUri('here')
              ->authenticate('123456');
    }

    /**
     * Test to make sure we build the appropriate sandbox url for member api token requests
     *
     * @return  void
     **/
    public function testBuildMemberSandboxTokenUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('setPostFields', 'setHeader')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://api.sandbox.orcid.org/oauth/token')->andReturn(m::self());

        // Tell the curl method to return an empty ORCID iD
        $response = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'response-success.json');
        $http->shouldReceive('execute')->andReturn($response);

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useSandboxEnvironment()
              ->useMembersApi()
              ->setClientId('1234')
              ->setClientSecret('12345')
              ->setRedirectUri('here')
              ->authenticate('123456');
    }

    /**
     * Test to make sure we can get a profile with the public api
     *
     * @return  void
     **/
    public function testGetPublicProfileUsesProperUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('execute', 'setHeader', 'setOpt')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://pub.orcid.org/v2.0/0000-0000-0000-0000/record');

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->getProfile('0000-0000-0000-0000');
    }

    /**
     * Test to make sure we can get a profile with the public api using an already established orcid
     *
     * @return  void
     **/
    public function testGetPublicProfileUsesProperUrlWithEstablishedOrcid()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('execute', 'setHeader', 'setOpt')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://pub.orcid.org/v2.0/0000-0000-0000-0000/record');

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->usePublicApi()->setOrcid('0000-0000-0000-0000')->getProfile();
    }

    /**
     * Test to make sure we can get a profile with the member api
     *
     * @return  void
     **/
    public function testGetMemberProfileUsesProperUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('execute', 'setHeader', 'setOpt')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://api.orcid.org/v2.0/0000-0000-0000-0000/record');

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useMembersApi()
              ->useProductionEnvironment()
              ->setAccessToken('123456789')
              ->getProfile('0000-0000-0000-0000');
    }

    /**
     * Test to make sure attempting to get a member profile without access token fails
     *
     * @expectedException  Exception
     * @return  void
     **/
    public function testGetMemberProfileWithNoAccessTokenThrowsException()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('execute', 'setHeader', 'setOpt')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://api.orcid.org/v2.0/0000-0000-0000-0000/record');

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useMembersApi()->getProfile('0000-0000-0000-0000');
    }

    /**
     * Test to make we use the proper sandbox url when fetching a sandbox profile
     *
     * @return  void
     **/
    public function testGetMemberSandboxProfileUsesProperUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('execute', 'setHeader', 'setOpt')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://api.sandbox.orcid.org/v2.0/0000-0000-0000-0000/record');

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->useMembersApi()
              ->useSandboxEnvironment()
              ->setAccessToken('123456789')
              ->getProfile('0000-0000-0000-0000');
    }

    /**
     * Test to make we use the proper sandbox url when fetching a sandbox profile
     *
     * @return  void
     **/
    public function testGetPublicSandboxProfileUsesProperUrl()
    {
        $http = m::mock('Orcid\Http\Curl');
        $http->shouldReceive('execute', 'setHeader', 'setOpt')->andReturn(m::self())
             ->getMock()
             ->shouldReceive('setUrl')->once()->with('https://pub.sandbox.orcid.org/v2.0/0000-0000-0000-0000/record');

        $oauth = m::mock('Orcid\Oauth', [$http])->makePartial();
        $oauth->usePublicApi()
              ->useSandboxEnvironment()
              ->setAccessToken('123456789')
              ->getProfile('0000-0000-0000-0000');
    }
}
