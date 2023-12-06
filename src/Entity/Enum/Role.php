<?php
namespace App\Entity\Enum;

enum Role: string
{
    case WORKER = 'ROLE_WORKER';
    case PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';
    case TEAM_LEADER = 'ROLE_TEAM_LEADER';
    case ADMIN = 'ROLE_ADMIN';
}