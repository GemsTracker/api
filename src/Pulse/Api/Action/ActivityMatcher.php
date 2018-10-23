<?php


namespace Pulse\Api\Action;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class ActivityMatcher implements MiddlewareInterface
{

    public $sedations = [
        73 => [
            'lokaal' => 47,
            'narc' => 50,
        ],
        79 => [
            'lokaal' => 46,
            'narc' => 52,
        ],
        80 => [
            'lokaal' => 48,
            'narc' => 51,
        ],
    ];


    public function __construct(\Gems_Agenda $agenda)
    {

        $this->agenda = $agenda;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $activities = json_decode($request->getBody()->getContents(), true);

        foreach($activities as $activity) {
            $this->agenda->matchActivity($activity['activity'], $activity['organization_id']);
            $this->addActivity2Sedation($activity['activity'], $activity['organization_id']);
        }
    }

    public function addActivity2Sedation($activityName, $organizationId)
    {
        if (strpos($activityName, 'OK') === 0) {
            if (strpos($activityName, 'Lokaal') !== false) {
                $sedationId = $this->sedations[$organizationId]['lokaal'];
                $weight = 10;
            } elseif (strpos($activityName, 'Sedatie') !== false || strpos($activityName, 'Plexus') !== false) {
                $sedationId = $this->sedations[$organizationId]['narc'];
                $weight = 20;
            } elseif (strpos($activityName, 'Narcose') !== false) {
                $sedationId = $this->sedations[$organizationId]['narc'];
                $weight = 30;
            }

            echo sprintf("INSERT INTO pulse__activity2sedation (pa2s_activity, pa2s_id_sedation, pa2s_weight, pa2s_changed, pa2s_changed_by, pa2s_created, pa2s_created_by) VALUES ('%s', %d, %d, NOW(), 0, NOW(), 0);", $activityName, $sedationId, $weight);
            echo "\n";
        }
    }
}