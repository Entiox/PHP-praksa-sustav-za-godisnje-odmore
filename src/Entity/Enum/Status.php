<?php
namespace App\Entity\Enum;

enum Status: string
{
    case PENDING_BOTH = 'PENDING_BOTH';
    case PENDING_PROJECT_MANAGER = 'PENDING_PROJECT_MANAGER';
    case PENDING_TEAM_LEADER = 'PENDING_TEAM_LEADER';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}