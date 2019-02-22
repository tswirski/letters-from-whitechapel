<?php

/**
 * Description of Pathfinder
 *
 * @author Administrator
 *
 * Mind That!
 * Kohana Query Builder throws exception if empty array passed to "IN" or "NOT IN".
 * Therefor we add '0' to that list. It is safe because database index starts with '1';
 */
class Game_Pathfinder {

    /**
     * Return jack available start points.
     * @return array
     */
    public static function getJackAvailableStartPoints() {
        return DB::select(Model_Hideout::COLUMN_ID)
            ->from('hideouts')
            ->where(Model_Hideout::COLUMN_IS_WRETCHED_STARTPOINT, 'is', NULL)
            ->as_assoc(Model_Hideout::COLUMN_ID)
            ->execute()
            ->as_array(null, Model_Hideout::COLUMN_ID);
    }

    /**
     * Return wretched asailable start points
     * @param array $murderScenes
     */
    public static function getWretchedAvailableStartPoints(array $murderScenes) {
        $murderScenes[] = 0;

        return DB::select(Model_Hideout::COLUMN_ID)
            ->from('hideouts')
            ->where(Model_Hideout::COLUMN_ID, 'NOT IN', $murderScenes)
            ->where(Model_Hideout::COLUMN_IS_WRETCHED_STARTPOINT, '=', 1)
            ->as_assoc(Model_Hideout::COLUMN_ID)
            ->execute()
            ->as_array(null, Model_Hideout::COLUMN_ID);
    }

    /**
     * Return list of policeman available base start points (black square with yellow border on the board).
     * Those which are currently occupied by Police Officer can be optionally removed.
     * @param array $policemanPositions
     * @return array
     */
    public static function getPolicemanAvailableBaseStartPoints(array $policemanPositions = []) {
        $policemanPositions[] = 0;

        return DB::select(Model_Hideout::COLUMN_ID)
            ->from('junctions')
            ->where(Model_Junction::COLUMN_ID, "NOT IN", $policemanPositions)
            ->where(Model_Junction::COLUMN_IS_POLICEMAN_STARTPOINT, '=', 1)
            ->as_assoc(Model_Junction::COLUMN_ID)
            ->execute()
            ->as_array(null, Model_Junction::COLUMN_ID);
    }


    /**
     * Returns HideoutId which are available for Woman to walk to.
     * @param $womanHideoutId where Woman is
     * @param array $policemanPositions
     * @param array $womenPositions
     * @param array $murderScenes
     * @return array
     */
    public static function getWretchedAvailableMoves($womanHideoutId, array $policemanPositions, array $womenPositions, array $murderScenes)
    {
        $policemanPositions[] = 0;
        $canWalkTo = self::_jackWalk($womanHideoutId, $policemanPositions);
        $observed = self::getPoliceOfficerAvailableActionHideoutIDs($policemanPositions);
        return array_diff($canWalkTo, $observed, $womenPositions, $murderScenes);
    }


    /**
     * Return list of available locations assinged to move type.
     * @param int $jackPosition
     * @param array $policemanPositions
     * @return array
     */
    public static function getJackAvailableMoves($jackPosition, array $policemanPositions) {
        $availableMoves = [
            'walk' => self::_jackWalk($jackPosition, $policemanPositions),
            'carriage' => self::_jackCarriage($jackPosition),
            'alley' => self::_jackAlley($jackPosition)
        ];

        return $availableMoves;
    }

    /**
     * Jack moves by foot
     * @param int $jackPosition
     * @param array $policemanPositions
     * @return array
     */
    protected static function _jackWalk($jackPosition, array $policemanPositions) {
        $policemanPositions[] = 0;

        $closestJunctions = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $jackPosition)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, 'NOT IN', $policemanPositions)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        if (!$closestJunctions) {
            return [];
        }

        $traversedJunctions = $closestJunctions;
        do {
            $closestJunctions = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
                ->from('correlations')
                ->where(Model_Correlation::COLUMN_BASE_NODE_ID, 'IN', $closestJunctions)
                ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
                ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, 'NOT IN', $traversedJunctions)
                ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, 'NOT IN', $policemanPositions)
                ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
                ->where(Model_Correlation::COLUMN_DIRECT, '=', true)
                ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
                ->execute()
                ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

            $traversedJunctions = array_merge($traversedJunctions, $closestJunctions);
        } while ($closestJunctions);

        $oneStepLocations = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, 'IN', $traversedJunctions)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, '!=', $jackPosition)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);


        $oneStepLocations = array_unique($oneStepLocations);
        sort($oneStepLocations);
        return $oneStepLocations;
    }

    /**
     * @param int $fromHideoutId
     * @param int $toHideoutId
     * @throws Exception if carriage move is not possible between $from and $to locations
     * @returns array of location that are 'first' step of carriage move between $from and $to locations.
     */
    public static function getCarriageIntermediate($fromHideoutId, $toHideoutId){
        if($fromHideoutId == $toHideoutId){
            throw new Exception("Starting and Ending point can not be the same ID");
        }

        $fromNeighbours = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $fromHideoutId)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        $toNeighbours = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $toHideoutId)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        return array_intersect($fromNeighbours, $toNeighbours);
    }

    /**
     * Jack moves with Carriage
     * @param int $jackPosition
     * @return array
     */
    protected static function _jackCarriage($jackPosition) {
        $closestHideouts = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $jackPosition)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        $secondStepHideouts = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, 'IN', $closestHideouts)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, '!=', $jackPosition)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        // $carriageLocations = array_merge($closestHideouts, $secondStepHideouts);
        $carriageLocations = array_unique($secondStepHideouts);
        sort($carriageLocations);
        return $carriageLocations;
    }

    /**
     * Jack moves through Alley
     * @param int $jackPosition
     * @return array
     */
    protected static function _jackAlley($jackPosition) {
        $districts = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $jackPosition)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_DISTRICT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);


        $hideouts = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, 'IN', $districts)
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_DISTRICT)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, '!=', $jackPosition)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        $hideouts = array_unique($hideouts);
        sort($hideouts);
        return $hideouts;
    }

    /**
     * Get hideout IDs available for Policeman to take action.
     * @param  array | int $policemanPosition
     * @returns array
     */
    public static function getPoliceOfficerAvailableActionHideoutIDs($policemanPosition){
        return DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_HIDEOUT)
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, is_array($policemanPosition) ? 'IN' : '=' , $policemanPosition)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);
    }

    /**
     * Returns list of all junctionIDs available for policeman to move to.
     * Up to two moves excluding other policeman positions.
     * @param int $subjectPoliceOfficerJunctionId
     * @param array $policeOfficersJunctionIDs
     */
    public static function getPoliceOfficerAvailableMoves($subjectPoliceOfficerJunctionId, array $policeOfficersJunctionIDs) {

        $closestJunctions = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, '=', $subjectPoliceOfficerJunctionId)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);

        $secondStepJunctions = DB::select(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->from('correlations')
            ->where(Model_Correlation::COLUMN_BASE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_TYPE, '=', Model_Correlation::NODE_JUNCTION)
            ->where(Model_Correlation::COLUMN_BASE_NODE_ID, 'IN', $closestJunctions)
            ->where(Model_Correlation::COLUMN_REMOTE_NODE_ID, '!=', $subjectPoliceOfficerJunctionId)
            ->as_assoc(Model_Correlation::COLUMN_REMOTE_NODE_ID)
            ->execute()
            ->as_array(null, Model_Correlation::COLUMN_REMOTE_NODE_ID);


        $junctions = array_merge($closestJunctions, $secondStepJunctions);
        $junctions = array_unique($junctions);
        sort($junctions);
        return array_diff($junctions, $policeOfficersJunctionIDs);
    }



}
