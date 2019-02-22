<?php

/**
 * Client Request Batch Support
 */
class JsonRpc_Client_Batch {

    /** Request Queue */
    protected $batch = array();

    /**
     * Add REQUEST to batch
     * @param string $method
     * @param callable | null $successCallback
     * @param callable | null $errorCallback
     * @param array $params
     * @return \JsonRpc_Client_Batch
     */
    public function request($method, $successCallback = null, $errorCallback = null, array $params = null) {
        $this->batch[] = JsonRpc::client()->getRequestObject($method, $successCallback, $errorCallback, $params);
        return $this;
    }

    /**
     * Add NOTIFICATION to batch;
     * @param string $method
     * @param array $params
     * @return \JsonRpc_Client_Batch
     */
    public function notify($method, array $params = null) {
        $this->batch[] = JsonRpc::client()->getNotificationObject($method, $params);
        return $this;
    }

    /**
     * Sends BATCH
     * @param int | array $connectionID
     */
    public function send($connectionID) {
        JsonRpc::send($connectionID, $this->get());
    }

    /**
     * Get Batch Content
     * @return  {array}
     */
    public function get() {
        return $this->batch;
    }

}
