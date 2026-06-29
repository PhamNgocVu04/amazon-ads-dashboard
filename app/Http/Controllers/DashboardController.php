<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function campaigns(Request $request): JsonResponse
    {
        $accountId = config('amazon_ads.account_id', 2);
        $yesterday = $request->input('to', now()->subDay()->format('Y-m-d'));
        $weekAgo   = $request->input('from', now()->subDays(7)->format('Y-m-d'));
        $data = DB::table('mkp_amz_ads_campaigns_daily as cd')
            ->leftJoin('mkp_amz_ads_campaigns_listing as cl', function ($join) {
                $join->on('cd.account_id', '=', 'cl.account_id')
                    ->on('cd.mkp_id', '=', 'cl.mkp_id')
                    ->on('cd.api_type', '=', 'cl.api_type')
                    ->on('cd.campaign_id', '=', 'cl.campaign_id');
            })
            ->selectRaw('COALESCE(cl.name, "") as name, cd.api_type, COALESCE(cl.portfolio_id, "") as portfolio, COALESCE(cl.serving_status, cl.state, "") as status, COALESCE(cl.daily_budget, 0) as budget, cd.campaign_id, cd.account_id, cd.mkp_id, COALESCE(cl.targeting_type, "") as targeting_type, SUM(cd.impressions) as impressions_7d, SUM(cd.clicks) as clicks_7d, SUM(cd.cost) as spend_7d, SUM(COALESCE(cd.conversions_7d, 0)) as orders_7d, SUM(COALESCE(cd.sales_7d, 0)) as sales_7d, SUM(IF(cd.report_date = ?, cd.impressions, 0)) as impressions_today, SUM(IF(cd.report_date = ?, cd.clicks, 0)) as clicks_today, SUM(IF(cd.report_date = ?, cd.cost, 0)) as spend_today, SUM(IF(cd.report_date = ?, COALESCE(cd.conversions_7d, 0), 0)) as orders_today, SUM(IF(cd.report_date = ?, COALESCE(cd.sales_7d, 0), 0)) as sales_today', [$yesterday, $yesterday, $yesterday, $yesterday, $yesterday])
            ->where('cd.account_id', $accountId)
            ->whereDate('cd.report_date', '>=', $weekAgo)
            ->whereDate('cd.report_date', '<=', $yesterday)
            ->groupBy('cd.account_id','cd.mkp_id','cd.api_type','cd.campaign_id','cl.name','cl.portfolio_id','cl.state','cl.serving_status','cl.daily_budget','cl.targeting_type')
            ->orderBy('cl.name')->get()->map(function ($r) {
                $r->ctr_7d = $r->impressions_7d > 0 ? round($r->clicks_7d / $r->impressions_7d * 100, 2) : 0;
                $r->cpc_7d = $r->clicks_7d > 0 ? round($r->spend_7d / $r->clicks_7d, 4) : 0;
                $r->acos_7d = $r->sales_7d > 0 ? round($r->spend_7d / $r->sales_7d * 100, 2) : 0;
                $r->roas_7d = $r->spend_7d > 0 ? round($r->sales_7d / $r->spend_7d, 2) : 0;
                return $r;
            });
        return response()->json(['account_id'=>$accountId,'period'=>['from'=>$weekAgo,'to'=>$yesterday],'count'=>$data->count(),'campaigns'=>$data]);
    }

    public function daily(Request $request): JsonResponse
    {
        $accountId = config('amazon_ads.account_id', 2);
        $days = min((int)$request->input('days', 14), 60);
        $startDate = $request->input('from', now()->subDays($days)->format('Y-m-d'));
        $endDate = $request->input('to', now()->subDay()->format('Y-m-d'));
        $data = DB::table('mkp_amz_ads_campaigns_daily')->where('account_id', $accountId)
            ->whereDate('report_date', '>=', $startDate)
            ->whereDate('report_date', '<=', $endDate)
            ->selectRaw('DATE_FORMAT(report_date, "%Y-%m-%d") as date, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(cost) as spend, SUM(COALESCE(conversions_7d, 0)) as orders, SUM(COALESCE(sales_7d, 0)) as sales')
            ->groupBy('report_date')->orderBy('report_date')->get()->map(function ($r) {
                $r->acos = $r->sales > 0 ? round($r->spend / $r->sales * 100, 2) : 0;
                $r->ctr = $r->impressions > 0 ? round($r->clicks / $r->impressions * 100, 2) : 0;
                return $r;
            });
        return response()->json(['daily'=>$data]);
    }
}
