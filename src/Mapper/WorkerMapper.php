<?php

namespace App\Mapper;

class WorkerMapper
{
    public static function mapVacationData($availableVacationDays, $vacationRequests)
    {
        $data['availableVacationDays'] = $availableVacationDays;
        $data['vacationRequests'] = [];

        foreach ($vacationRequests as $vacationRequest) {
            $startingYear = $vacationRequest->getStartingDate()->format('Y');
        
            $status = str_contains($vacationRequest->getStatus(), 'PENDING') ? 'PENDING' : $vacationRequest->getStatus();
            $data['vacationRequests'][$startingYear][strtolower($status)][] = [
                'startingDate' => $vacationRequest->getStartingDate()->format('d.m.Y.'),
                'endingDate' => $vacationRequest->getEndingDate()->format('d.m.Y.'),
            ];
        }

        krsort($data['vacationRequests']);

        return $data;
    }
}
