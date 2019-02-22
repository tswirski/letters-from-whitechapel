<?php


/**
 * Created by PhpStorm.
 * User: lama
 * Date: 2016-04-08
 * Time: 18:47
 */

class Game_Role{
    const JACK = 'jack';
    const RED_POLICE_OFFICER = 'redPoliceOfficer';
    const BLUE_POLICE_OFFICER = 'bluePoliceOfficer';
    const YELLOW_POLICE_OFFICER = 'yellowPoliceOfficer';
    const GREEN_POLICE_OFFICER = 'greenPoliceOfficer';
    const BROWN_POLICE_OFFICER = 'brownPoliceOfficer';

    const NAME_JACK = "Jack The Ripper";
    const NAME_RED_POLICE_OFFICER = "Frederick Abberline";
    const NAME_BLUE_POLICE_OFFICER = "Sir Charles Warren";
    const NAME_YELLOW_POLICE_OFFICER = "George Lusk";
    const NAME_GREEN_POLICE_OFFICER = "Edmund Reid";
    const NAME_BROWN_POLICE_OFFICER = "Donald Swanson";

    const COLOR_NAME_RED_POLICE_OFFICER = "Red Police Officer";
    const COLOR_NAME_BLUE_POLICE_OFFICER = "Blue Police Officer";
    const COLOR_NAME_YELLOW_POLICE_OFFICER = "Yellow Police Officer";
    const COLOR_NAME_GREEN_POLICE_OFFICER = "Green Police Officer";
    const COLOR_NAME_BROWN_POLICE_OFFICER = "Brown Police Officer";

    public static function getAllRolesArray(){
        return [
            self::JACK,
            self::RED_POLICE_OFFICER,
            self::BLUE_POLICE_OFFICER,
            self::YELLOW_POLICE_OFFICER,
            self::BROWN_POLICE_OFFICER,
            self::GREEN_POLICE_OFFICER
        ];
    }

    public static function getPoliceRolesArray(){
        return [
            self::BLUE_POLICE_OFFICER,
            self::YELLOW_POLICE_OFFICER,
            self::RED_POLICE_OFFICER,
            self::GREEN_POLICE_OFFICER,
            self::BROWN_POLICE_OFFICER
        ];
    }

    public static function isPoliceOfficerRole($role){
        return in_array($role, self::getPoliceRolesArray());
    }

    public static function getNameForRole($role){
        switch($role){
            case self::RED_POLICE_OFFICER:
                return self::NAME_RED_POLICE_OFFICER;
                break;

            case self::BLUE_POLICE_OFFICER:
                return self::NAME_BLUE_POLICE_OFFICER;
                break;

            case self::YELLOW_POLICE_OFFICER:
                return self::NAME_YELLOW_POLICE_OFFICER;
                break;

            case self::GREEN_POLICE_OFFICER:
                return self::NAME_GREEN_POLICE_OFFICER;
                break;

            case self::BROWN_POLICE_OFFICER:
                return self::NAME_BROWN_POLICE_OFFICER;
                break;

            default:
                throw new Exception("Invalid role [{$role}]");
        }
    }

    public static function getColorNameForRole($role){
        switch($role){
            case self::RED_POLICE_OFFICER:
                return self::COLOR_NAME_RED_POLICE_OFFICER;
                break;

            case self::BLUE_POLICE_OFFICER:
                return self::COLOR_NAME_BLUE_POLICE_OFFICER;
                break;

            case self::YELLOW_POLICE_OFFICER:
                return self::COLOR_NAME_YELLOW_POLICE_OFFICER;
                break;

            case self::GREEN_POLICE_OFFICER:
                return self::COLOR_NAME_GREEN_POLICE_OFFICER;
                break;

            case self::BROWN_POLICE_OFFICER:
                return self::COLOR_NAME_BROWN_POLICE_OFFICER;
                break;

            default:
                throw new Exception("Invalid role [{$role}]");
        }
    }
}