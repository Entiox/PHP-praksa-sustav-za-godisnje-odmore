<?php

namespace App\Mapper;

class AdminMapper
{
    public static function mapEmployees($employees)
    {
        return array_map(function ($employee) {
            return [
                'id' => $employee->getId(),
                'email' => $employee->getEmail(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
                'team' => $employee->getTeam() ? $employee->getTeam()->getName() : 'No team',
                'roles' => implode(', ', array_map([UserMapper::class, 'mapRoleName'], $employee->getRoles())),
            ];
        }, $employees);
    }
}
