<?php

namespace ClassCentral\SiteBundle\Services;

use Guzzle\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class React
{
    private $container;
    private $cacheReact;

    public function __construct(ContainerInterface $container, $cacheReact)
    {
        $this->container = $container;
        $this->cacheReact = $cacheReact;
    }

    public function component($name, $request_type = false, $data = [])
    {
        $cache = $this->container->get('cache');
        $detect = new \Mobile_Detect();

        $cached = true;
        if(!empty($this->container->get('security.context')->getToken()) && $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $cached = false;
        }

        $query = $this->container->get('request')->get('q');
        if(!empty($query))
        {
            $cached = false;
        }

        $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
        if($this->cacheReact && $cached)
        {
            return $cache->get("react_component_{$deviceType}_" . $name . '_' . $request_type, [$this, 'getComponent'], [$name, $request_type, $data]);
        }
        else
        {
            return $this->getComponent($name, $request_type, $data);
        }
    }

    public function getComponent($name, $request_type, $data)
    {
        try
        {
            $reactServerUrl = $this->container->getParameter('react_server');
            $httpRequestObject = $this->container->get('request');
            $client = new Client();
            $request = $client->post($reactServerUrl.'/' . $this->getComponentPath($name), array(
                'content-type' => 'application/json',
                'User-Agent' => $httpRequestObject->headers->get('User-Agent')
            ));
            $request->setBody(json_encode($data));
            $response = $request->send();
            $content = json_decode($response->getBody(), true);

            if ($request_type == 'state') {
                return json_encode($content['initialState']);
            } else {
                return $content['componentString'];
            }
        } catch ( \Exception $e)
        {

          if ($request_type == 'state') {
            return json_encode($data);
          } else {
            return "";
          }
        }
    }

    public function getComponentPath($name) {
      $path = $name;

      if ($name === "reviewSearch") {
        $path = "search";
      }

      return $path;
    }
}
