<?php

namespace App\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\OptionRepository;

class OptionService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected OptionRepository $OptionRepository)
    {}


    public function getOption($data=[], $debug = 0)
    {
        return $this->OptionRepository->getOption($data, $debug);
    }


    public function getOptions($data=[], $debug = 0)
    {
        return $this->OptionRepository->getOptions($data, $debug);
    }


    public function getValues($data=[], $debug = 0)
    {
        return $this->OptionRepository->getValues($data, $debug);
    }


    public function deleteOptionById($option_id)
    {
        return $this->OptionRepository->deleteOptionById($option_id);
    }


    public function getProductCountByOptionId($option_id)
    {
        return $this->OptionRepository->getProductCountByOptionId($option_id);
    }

}