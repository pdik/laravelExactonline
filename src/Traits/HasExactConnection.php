<?php
namespace Pdik\src\Traits;
/**
 * HasExactConnection
 */
trait HasExactConnection{

    /**
     * Get exact connection
     * @return mixed
     */
    public function exactAble()
    {
        return $this->morphTo();
    }
}