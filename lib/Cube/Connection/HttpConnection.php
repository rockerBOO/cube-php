<?php
namespace Cube\Connection;
class HttpConnection extends \Cube\Connection\Connection {
    public $connected = false, $evaluator_evaluator_uri, $collector_uri;

    /**
     * @param array $conf
     *    host: string required ip or hostname
     *    port: int required
     *    secure: bool optional use https?
     */
    public function init(array $conf)
    {
        $secure = empty($conf['secure']) ? '' : 's';
        $this->evaluator_uri = sprintf('http%s://%s:%s/', $secure, $conf['evaluator']['host'], $conf['evaluator']['port']);
        $this->collector_uri = sprintf('http%s://%s:%s/', $secure, $conf['collector']['host'], $conf['collector']['port']);

        // Needed to tell httpful to use arrays instead of plain objects for json responses
        \Httpful\Httpful::register(\Httpful\Mime::JSON, new \Httpful\Handlers\JsonHandler(array('decode_as_array' => true)));
    }

    public function eventGet($args)
    {
        $query = http_build_query($args);
        $res = $this->send($this->evaluator_uri . '1.0/event/get?' . $query);
        return $res->body;
    }

    /**
     * @return array associative array response
     * @param array $args
     *    array('expression' => , 'start' => , 'stop' => , 'limit' => , 'step' => );
     */
    public function metricGet($args)
    {
        $query = http_build_query($args);
        $res = $this->send($this->evaluator_uri . '1.0/metric/get?' . $query);
        return $res->body;
    }

    /**
     * @return array associative array response
     * @param array not applicable
     */
    public function typesGet($args = null)
    {
        $res = $this->send($this->evaluator_uri . '1.0/types/get');
        return $res->body;
    }

    /**
     * @return array associative array response
     * @param array $args array('time' => , 'type' => , 'data' => )
     */
    public function eventPut($args)
    {
        $args = \Cube\Command::prepPayload($args);
        $req = \Httpful\Request::post($this->collector_uri . '1.0/event/put')
            ->sendsJson()
            ->expectsJson()
            ->body($args);

        try {
            $res = $req->send();
        } catch (Httpful\Exception\ConnectionErrorException $e) {
            throw new \Cube\Exception\ConnectionException($e->getMessage(), $e->getCode());
        }
        
        return $res->body;
    }

    private function send($url)
    {
        $res = \Httpful\Request::get($url)->expectsJson()->send();
        return $res;
    }
}


