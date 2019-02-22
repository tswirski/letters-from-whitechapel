<?php

class Controller_Development extends Controller
{

    /**
     * JSON RPC INITIALIZATION
     */
    public function before()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="My Realm"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Brak Autoryzacji';
            exit;
        } else {
            if($_SERVER['PHP_AUTH_USER'] !== 'tomek' || $_SERVER['PHP_AUTH_PW'] !== 'chuj666'){
                die;
            }
        }

        JsonRpc::server()
            ->register('createNode', [$this, 'createNode'])
            ->register('removeNode', [$this, 'removeNode'])
            ->register('moveNode', [$this, 'moveNode'])
            ->register('getCorrelatedNodes', [$this, 'getCorrelatedNodes'])
            ->register('setNodeCorrelation', [$this, 'setNodeCorrelation'])
            ->register('unSetNodeCorrelation', [$this, 'unSetNodeCorrelation'])
            ->register('getCorrelatedJunctions', [$this, 'getCorrelatedJunctions'])
            ->register('setDirectJunction', [$this, 'setDirectJunction'])
            ->register('unSetDirectJunction', [$this, 'unSetDirectJunction']);
    }


    /**
     * JSON RPC ENDPOINT
     */
    public function action_jsonrpc()
    {
        $json = file_get_contents("php://input");

        /** Hack używany przy transferze plików */
        if (!$json) {
            $json = Arr::get($_POST, 'json');
        }

        echo JsonRpc::dispatch($json);
    }


    /**
     * INDEX
     */
    public function action_index()
    {
        echo View::factory('development/board', [
            'mode' => 'directJunctions'
        ])->render();
    }

    /**
     * Get model name for node by type
     * @param string $nodeType
     * @return string | null
     */
    protected function getNodeModelName($nodeType){
        $nodeType = strtolower($nodeType);

        if($nodeType === 'district'){
            return Model_District::class;
        }

        if($nodeType === 'junction'){
            return Model_Junction::class;
        }

        if($nodeType === 'hideout'){
            return Model_Hideout::class;
        }
    }

    /**
     * Create new node by type.
     * @param string $nodeType 'district', 'hideout' or 'junction'
     * @param float $positionX percent left
     * @param float $positionY percent top
     * @return int
     */
    public function createNode($nodeType, $positionX, $positionY)
    {
        $id = DAO::find($this->getNodeModelName($nodeType))->order_by('id', 'DESC')->getFirst()->id + 1;
        $node = DAO::factory($this->getNodeModelName($nodeType), $id);
        $node->position_x = $positionX;
        $node->position_y = $positionY;
        $node->insert();

        return JsonRpc::client()->getNotificationObject('development.createBoardNode', [
            'nodeType' => $nodeType,
            'id' => $id,
            'positionX' => $positionX,
            'positionY' => $positionY
        ]);
    }

    /**
     * remove node by type.
     * @param string $nodeType 'district', 'hideout' or 'junction'
     * @param int id
     * @return boolean
     */
    public function removeNode($nodeType, $id)
    {
        $node = DAO::factory($this->getNodeModelName($nodeType), (int) $id);
        $node->delete();

        return JsonRpc::client()->getNotificationObject('development.removeBoardNode', [
            'nodeType' => $nodeType,
            'id' => $id
        ]);
    }

    /**
     * Move node to X, Y
     * @param string $nodeType 'district', 'hideout' or 'junction'
     * @param int $id
     * @param float $positionX percent left
     * @param float $positionY percent top
     * @return bool
     */
    public function moveNode($nodeType, $id, $positionX, $positionY)
    {
        $node = DAO::factory($this->getNodeModelName($nodeType), (int) $id);
        $node->position_x = $positionX;
        $node->position_y = $positionY;
        $node->update();

        return true;
    }

    /**
     * @param string $nodeType 'district', 'hideout' or 'junction'
     * @param int $id
     * @return array
     */
    public function getCorrelatedNodes($nodeType, $id)
    {
        $correlated_nodes = DAO::find(Model_Correlation::class)
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', (int) $id)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', $nodeType)
            ->getAll();

        $correlations = array();
        foreach ($correlated_nodes as $node) {
            $correlations[] = [
                'nodeType' => $node->get(Model_Correlation::COLUMN_REMOTE_NODE_TYPE),
                'id' => $node->get(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ];
        }

        return $correlations;
    }

    /**
     * Set or remove node correlation
     * @param string $baseNodeType
     * @param int $baseNodeId
     * @param string $remoteNodeType
     * @param int $remoteNodeId
     * @param boolean $set
     */
    public function _setNodeCorrelation($baseNodeType, $baseNodeId, $remoteNodeType, $remoteNodeId, $set)
    {
        if($baseNodeType === $remoteNodeType && $baseNodeId === $remoteNodeId){
            throw new Exception ("remote node same as base node");
        }

        $base_node_key = array(
            'base_node_id' => $baseNodeId,
            'base_node_type' => $baseNodeType,
            'remote_node_id' => $remoteNodeId,
            'remote_node_type' => $remoteNodeType);

        $remote_node_key = array(
            'base_node_id' => $remoteNodeId,
            'base_node_type' => $remoteNodeType,
            'remote_node_id' => $baseNodeId,
            'remote_node_type' => $baseNodeType);

        $base_node = DAO::factory('Correlation', $base_node_key);
        $remote_node = DAO::factory('Correlation', $remote_node_key);

        if ($set === true) {
            $base_node->insert();
            $remote_node->insert();
        } else {
            $base_node->delete();
            $remote_node->delete();
        }
    }

    /**
     * Set node correlation
     * @param string $baseNodeType
     * @param int $baseNodeId
     * @param string $remoteNodeType
     * @param int $remoteNodeId
     * @param boolean $set
     * @return true
     */
    public function setNodeCorrelation($baseNodeType, $baseNodeId, $remoteNodeType, $remoteNodeId)
    {
        $this->_setNodeCorrelation($baseNodeType, $baseNodeId, $remoteNodeType, $remoteNodeId, true);
        return true;
    }

    /**
     * Unset node correlation
     * @param string $baseNodeType
     * @param int $baseNodeId
     * @param string $remoteNodeType
     * @param int $remoteNodeId
     * @param boolean $set
     * @return true
     */
    public function unSetNodeCorrelation($baseNodeType, $baseNodeId, $remoteNodeType, $remoteNodeId)
    {
        $this->_setNodeCorrelation($baseNodeType, $baseNodeId, $remoteNodeType, $remoteNodeId, false);
        return true;
    }

    /**
     * Return Direct junctions for given base junction.
     * Direct junctions are those two junctions which are 'one-step' from each other
     * and are not separated by hideout.
     * @param int $junctionId
     */
    public function getCorrelatedJunctions($id){
        $correlatedJunctions = DAO::find('Correlation')
                ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $id)
                ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
                ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
                ->getAll();

        $correlatedJunctionData = array();
        foreach ($correlatedJunctions as $junction) {
            $correlatedJunctionData[] = [
                'id' => $junction->get(Model_Correlation::COLUMN_REMOTE_NODE_ID),
                'direct' => $junction->get(Model_Correlation::COLUMN_DIRECT)
            ];
        }

        return $correlatedJunctionData;
    }

    /**
     * Set or Unset direct junction flag
     * @param int $baseId base junction id
     * @param int $remoteId remote junction id
     * @param boolean $set true to set, false to unset
     */
    public function _setDirectJunction($baseId, $remoteId, $set) {
        if($baseId === $remoteId){
            throw new Exception("Can not correlate junction with it self");
        }

        $base_node_key = [
            'base_node_id' => $baseId,
            'base_node_type' => Model_Correlation::NODE_JUNCTION,
            'remote_node_id' => $remoteId,
            'remote_node_type' => Model_Correlation::NODE_JUNCTION
        ];

        $remote_node_key = [
            'base_node_id' => $remoteId,
            'base_node_type' => Model_Correlation::NODE_JUNCTION,
            'remote_node_id' => $baseId,
            'remote_node_type' => Model_Correlation::NODE_JUNCTION
        ];

        $baseNode = DAO::factory('Correlation', $base_node_key);
        $baseNode->set(Model_Correlation::COLUMN_DIRECT, $set);
        $baseNode->update();

        $remoteNode = DAO::factory('Correlation', $remote_node_key);
        $remoteNode->set(Model_Correlation::COLUMN_DIRECT, $set);
        $remoteNode->update();

        return true;
    }

    /**
     * Set direct junction flag
     * @param int $baseId base junction id
     * @param int $remoteId remote junction id
     */
    public function setDirectJunction($baseId, $remoteId){
        return $this->_setDirectJunction($baseId, $remoteId, true);
    }

    /**
     * Unset direct junction flag
     * @param int $baseId base junction id
     * @param int $remoteId remote junction id
     */
    public function unSetDirectJunction($baseId, $remoteId){
        return $this->_setDirectJunction($baseId, $remoteId, false);
    }



}





