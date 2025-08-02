<?php
/*
public function map(): void {
    $json = [];

    $this->load->model('extension/opencart/dashboard/map');

    $results = $this->model_extension_opencart_dashboard_map->getTotalOrdersByCountry();

    foreach ($results as $result) {
        $json[strtolower($result['iso_code_2'])] = [
            'total'  => $result['total'],
            'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency'))
        ];
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
*/
$json = [];
header("Content-Type: application/json");
echo json_encode($json);




