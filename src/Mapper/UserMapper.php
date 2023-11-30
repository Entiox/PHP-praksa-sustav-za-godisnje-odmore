<?php

namespace App\Mapper;

class UserMapper
{
    public static function mapRoleName($roleName)
    {
        $parts = explode('_', $roleName);
        array_shift($parts);
        return implode(' ', array_map(function($part) { return strtoupper(substr($part, 0, 1))
            .strtolower(substr($part, 1, strlen($part) - 1)); }, $parts));
    }

    public static function mapPersonalData($data)
    {
        return ['username' => $data->getUsername(), 'firstName' => $data->getFirstName(),
            'lastName' => $data->getLastName(), 'team' => $data->getTeam() ? $data->getTeam()->getName() : 'None',
            'roles' =>  implode(', ', array_map(function($role) { return UserMapper::mapRoleName($role); }, $data->getRoles()))];
    }
}