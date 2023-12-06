<?php

namespace App\Mapper;

class LeaderMapper
{
    public static function mapWorkers($workers)
    {
        return array_map(function ($worker) {
            return [
                'id' => $worker->getId(),
                'firstName' => $worker->getFirstName(),
                'lastName' => $worker->getLastName(),
                'email' => $worker->getEmail(),
                'availableVacationDays' => $worker->getAvailableVacationDays()
            ];
        }, $workers);
    }

    public static function mapVacationRequestsToDates($vacationRequests)
    {
        return array_map(function ($vacationRequest) {
            return [
                'startingDate' => $vacationRequest->getStartingDate()->format('d.m.Y.'),
                'endingDate' => $vacationRequest->getEndingDate()->format('d.m.Y.')
            ];
        }, $vacationRequests);
    }

    public static function mapVacationRequestsForUsers($vacationRequests)
    {
        return array_map(function ($vacationRequest) {
            return [
                'id' => $vacationRequest->getId(),
                'workerId' => $vacationRequest->getUser()->getId(),
                'firstName' => $vacationRequest->getUser()->getFirstName(),
                'lastName' => $vacationRequest->getUser()->getLastName(),
                'startingDate' => $vacationRequest->getStartingDate()->format('d.m.Y.'),
                'endingDate' => $vacationRequest->getEndingDate()->format('d.m.Y.')
            ];
        }, $vacationRequests);
    }
}
