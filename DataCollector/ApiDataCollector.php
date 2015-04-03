<?php
namespace Plr\Bundle\ApiBundle\DataCollector;

use Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector;

class ApiDataCollector extends GuzzleDataCollector {

    /**
     * {@inheritdoc}
     */
    public function getCalls() {

        $calls = isset($this->data['calls']) ? $this->data['calls'] : array();
        for ($i = 0; $i < count($calls); $i++) {

            $calls[$i]["response"]["encodedBody"] = json_decode($calls[$i]["response"]["body"]);
            $calls[$i]["request"]["encodedBody"] = json_decode($calls[$i]["request"]["body"]);
        }

        return $calls;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'plr_api';
    }
}
