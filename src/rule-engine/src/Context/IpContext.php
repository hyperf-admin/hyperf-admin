<?php
namespace HyperfAdmin\RuleEngine\Context;

class IpContext extends ContextPluginAbstract
{
    protected $ip2Region;

    public function __construct()
    {
        $this->ip2Region = new \Ip2Region();
    }

    public function name(): string
    {
        return 'ip';
    }

    public function ip2region($ip)
    {
        if(!ip2long($ip)) {
            return false;
        }
        try {
            $region = $this->ip2Region->memorySearch($ip);
            // 国家|区域|省份|城市|ISP
            $region = explode('|', $region['region']);

            return [
                'country' => $region[0],
                'area' => $region[1],
                'province' => $region[2],
                'city' => $region[3],
                'isp' => $region[4],
            ];
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function getRegion($ip)
    {
        return $this->ip2region($ip);
    }

    public function getCountry($ip)
    {
        $region = $this->ip2region($ip);

        return $region['country'] ?? null;
    }

    public function getProvince($ip)
    {
        $region = $this->ip2region($ip);

        return $region['province'] ?? null;
    }

    public function getCity($ip)
    {
        $region = $this->ip2region($ip);

        return $region['city'] ?? null;
    }
}
