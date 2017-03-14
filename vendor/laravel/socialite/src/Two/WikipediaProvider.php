<?php

namespace Laravel\Socialite\Two;

use Exception;
use GuzzleHttp\ClientInterface;

class WikipediaProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['basic','writeapi','viewmyprivateinfo','sendemail','oauth','blockusers','createeditmovepage'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://en.wikipedia.org/wiki/Special:OAuth/initiate', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://en.wikipedia.org/wiki/Special:OAuth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://en.wikipedia.org/w/api.php?access_token='.$token;

        $response = $this->getHttpClient()->get($userUrl);

        $user = json_decode($response->getBody(), true);

        if (in_array('email', $this->scopes)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * Get the email for the given access token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getEmailByToken($token)
    {
        $emailsUrl = 'https://en.wikipedia.org/w/index.php?title=Special:ApiSandbox&action=query&format=json&prop=&list=&meta=userinfo&uiprop=email&access_token='.$token;

        try {
            $response = $this->getHttpClient()->get($emailsUrl);
        } catch (Exception $e) {
            return;
        }

        $emails = json_decode($response->getBody(), true);

        foreach ($emails['values'] as $email) {
            if ($email['type'] == 'email' && $email['is_primary'] && $email['is_confirmed']) {
                return $email['email'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['uuid'], 'nickname' => $user['username'],
            'name' => array_get($user, 'display_name'), 'email' => array_get($user, 'email'),
            'avatar' => array_get($user, 'links.avatar.href'),
        ]);
    }

    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true)['access_token'];
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'code' => $code, 'redirect_uri' => $this->redirectUrl, 'grant_type' => 'authorization_code',
        ];
    }
}
