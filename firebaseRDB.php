<?php
/*
 * class name: firebaseRDB
 * version: 1.0
 * author: Devisty
 */

class firebaseRDB {
    // กำหนด properties ที่ต้องใช้
    private $url;

    public function __construct($url=null) {
        if (isset($url)) {
            $this->url = $url;
        } else {
            throw new Exception("Database URL must be specified");
        }
    }

    private function grab($url, $method, $par=null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (isset($par)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $par);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $html = curl_exec($ch);
        curl_close($ch); // ปิดการเชื่อมต่อ curl ที่นี่
        return $html;
    }

    public function insert($table, $data) {
        $path = $this->url . "/$table.json";
        $grab = $this->grab($path, "POST", json_encode($data));
        return $grab;
    }

    public function update($table, $uniqueID, $data) {
        $path = $this->url . "/$table/$uniqueID.json";
        $grab = $this->grab($path, "PATCH", json_encode($data));
        return $grab;
    }

    public function delete($table, $uniqueID) {
        $path = $this->url . "/$table/$uniqueID.json";
        $grab = $this->grab($path, "DELETE");
        return $grab;
    }

    public function retrieve($dbPath, $queryKey=null, $queryType=null, $queryVal=null) {
        if (isset($queryType) && isset($queryKey) && isset($queryVal)) {
            $queryVal = urlencode($queryVal);
            if ($queryType == "EQUAL") {
                $pars = "orderBy=\"$queryKey\"&equalTo=\"$queryVal\"";
            } elseif ($queryType == "LIKE") {
                $pars = "orderBy=\"$queryKey\"&startAt=\"$queryVal\"";
            }
        }
        $pars = isset($pars) ? "?$pars" : "";
        $path = $this->url . "/$dbPath.json$pars";
        $grab = $this->grab($path, "GET");
        return $grab;
    }
}
?>
