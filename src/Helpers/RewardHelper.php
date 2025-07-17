<?php

namespace Acecoderz\LicenseManager\Helpers;

use Carbon\Carbon;
use App\Models\Leads;
use App\Models\Rto;
use App\Models\StateNew;
use App\Models\GridRule;
use App\Models\InsuranceProvider;
use App\Models\CustomerPolicyModel;
use Exception;

/**
 * RewardHelper
 *
 * This helper depends on several models and possibly other helpers from the main app.
 * Ensure all dependencies are available and properly imported in your Laravel application.
 */
class RewardHelper
{
    public static function checkRewardPoints(
        $lead,
        $providerId,
        $odPremium = 0,
        $tpPremium = 0,
        $netPremium = 0
    ) {
        try {
            $outOdComm = 0;
            $outTpComm = 0;
            $outNetComm = 0;
            $rewardPoints = 0;

            if($lead->quote_type == "motor"){
                $vehicleAge = 0;
                $case = $lead->policy_case;
                $bussinessType = "";
                if ($case == "new") {
                    $bussinessType = "New Business";
                } else {
                    $bussinessType = "Rollover";
                }
                if ($bussinessType) {
                    if ($bussinessType == "Rollover") {
                        if (isset($lead->registration_date)) {
                            $regDate = $lead->registration_date;
                            $regDate = Carbon::parse($regDate);
                            $currentDate = Carbon::now();
                            $age = $currentDate->diffInYears($regDate);
                            $vehicleAge = $age . " Year";
                        }
                    }
                }
                $vehicleType = "";
                if ($lead->vehicle_type == "bike") {
                    $vehicleType = "2 Wheeler";
                } elseif ($lead->vehicle_type == "car") {
                    $vehicleType = "4 Wheeler";
                } elseif ($lead->vehicle_type == "pcv") {
                    $vehicleType = "Commercial PCV";
                } elseif ($lead->vehicle_type == "gcv") {
                    $vehicleType = "Commercial GCV";
                } elseif ($lead->vehicle_type == "mcv") {
                    $vehicleType = "Misc D";
                }

                $modelId = $lead->model_id;

                $rto = Rto::select("id", "rtoCode", "state")
                    ->where("rtoCode", $lead->rto_code)
                    ->first();
                $state = StateNew::select("id", "state_name")
                    ->where("state_name", $rto->state)
                    ->first();

                $getMmv = self::getMmvById($lead->object_id);

                $vehicleBody =
                    isset($getMmv) &&
                    isset($getMmv["data"]) &&
                    isset($getMmv["data"]["body_type"])
                        ? $getMmv["data"]["body_type"]
                        : "";
                $vehicle =
                    isset($getMmv) &&
                    isset($getMmv["data"]) &&
                    isset($getMmv["data"]["vehicle_type"])
                        ? $getMmv["data"]["vehicle_type"]
                        : "";
                $fuelType =
                    isset($getMmv) &&
                    isset($getMmv["data"]) &&
                    isset($getMmv["data"]["fuel_type"])
                        ? $getMmv["data"]["fuel_type"]
                        : "";

                $vehicleCategory = app('App\\Helpers\\Helpers')::getVehicleCategory(
                    $vehicleType,
                    $vehicleBody,
                    $vehicle,
                    $fuelType
                );

                $data["agent_id"] = $lead->agent_id;
                $data["ncb"] = $lead->previous_no_claim_bonus;
                $data["provider"] = $providerId ?? null;
                $data["vehicle_type"] = $vehicleType;
                $data["vehicle_category"] = $vehicleCategory;
                $data["vehicle_age"] = $vehicleAge;
                $data["make_id"] = (string) $lead->make_id;
                $data["state_id"] = (string) $state->id;
                $data["rto_id"] = (string) $rto->id;
                $data["cc"] = "";
                $data["gvw"] = "";
                $data["pcv"] = "";
                if ($vehicleType == "2 Wheeler" || $vehicleType == "4 Wheeler") {
                    $data["cc"] =
                        getVehicleCapacity(
                            $vehicleType,
                            "cc",
                            $getMmv["data"]["providers"][0]["cc"]
                        ) ?? 0;
                }
                if ($vehicleType == "Commercial GCV") {
                    $data["gvw"] =
                        getVehicleCapacity(
                            $vehicleType,
                            "gvw",
                            $getMmv["data"]["providers"][0]["gvw"]
                        ) ?? 0;
                }
                if ($vehicleType == "Commercial PCV") {
                    $data["pcv"] =
                        getVehicleCapacity(
                            $vehicleType,
                            "pcv",
                            $getMmv["data"]["providers"][0]["seating_capacity"]
                        ) ?? 0;
                }
                $data["policy_type"] = $lead->product_code;
                $data["business_type"] = $bussinessType;
                $data["fuel_type"] = ucfirst($lead->fuel_type);

                $state_id = $data["state_id"];
                $rto_id = $data["rto_id"];
                $make_id = $data["make_id"];
                $fuel_type = $data["fuel_type"];

                $pcv = $data["pcv"];
                $gvw = $data["gvw"];
                $cc = $data["cc"];
                $vehicle_category = $data["vehicle_category"];

                $rules = GridRule::join(
                        "agent_payouts",
                        "grid_rules.id",
                        "=",
                        "agent_payouts.rule_id"
                    )
                    ->select(
                        "grid_rules.id",
                        "commission",
                        "commission_type",
                        "od_comm",
                        "tp_comm",
                        "net_comm",
                        "agent_payouts.payout",
                        "agent_payouts.od_payout",
                        "agent_payouts.tp_payout",
                        "agent_payouts.net_payout",
                        "agent_payouts.tp_payout_first_year",
                        "effective_start_date"
                    )
                    ->where("agent_payouts.agent_id", $data["agent_id"])
                    ->where("bussiness_type", $data["business_type"])
                    ->where("provider_id", $providerId)
                    ->where(function ($query) use ($state_id) {
                        if ($state_id) {
                            $query
                                ->whereJsonContains("state_id", $state_id)
                                ->orWhereJsonContains("state_id", "select_all");
                        }
                    })
                    ->where(function ($query) use ($rto_id) {
                        if ($rto_id) {
                            $query
                                ->whereJsonContains("rto_id", $rto_id)
                                ->orWhereJsonContains("rto_id", "select_all");
                        }
                    })
                    ->where(function ($query) use ($make_id) {
                        if (!empty($make_id)) {
                            $query->orWhereJsonContains("make_id", $make_id);
                        }
                    })
                    ->whereJsonContains("fuel_type", $data["fuel_type"])
                    ->where(function ($query) use ($vehicleAge, $data) {
                        if ($data["business_type"] == "New Business") {
                            $query->where("vehicle_age", "0 Year");
                        } else {
                            $query->whereRaw(
                                '? BETWEEN CAST(SUBSTRING_INDEX(vehicle_age, "-", 1) AS UNSIGNED) AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(vehicle_age, " ", 1), "-", -1) AS UNSIGNED)',
                                [$vehicleAge]
                            );
                        }
                    })
                    ->where("vehicle_type", $data["vehicle_type"])
                    ->whereJsonContains("vehicle_category", $data["vehicle_category"])
                    ->where("policy_type", $data["policy_type"])
                    ->when($cc, function ($query, $cc) {
                        return $query->whereJsonContains("cubic_capacity", $cc);
                    })
                    ->when($gvw, function ($query, $gvw) {
                        return $query->whereJsonContains("gvw", $gvw);
                    })
                    ->when($pcv, function ($query, $pcv) {
                        return $query->whereJsonContains("pcv", $pcv);
                    })
                    ->orderByDesc("effective_start_date")
                    ->first();

                if ($rules) {
                    $outOdComm = $rules->od_comm;
                    $outTpComm = $rules->tp_comm;
                    $outNetComm = $rules->net_comm;
                    $rewardPoints = $rules->payout;
                }
            }
            return [
                'od_comm' => $outOdComm,
                'tp_comm' => $outTpComm,
                'net_comm' => $outNetComm,
                'reward_points' => $rewardPoints,
            ];
        } catch (Exception $e) {
            return [
                'od_comm' => 0,
                'tp_comm' => 0,
                'net_comm' => 0,
                'reward_points' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function assignRewardPoints(
        $enquiryId,
        $providerId,
        $odPremium = 0,
        $tpPremium = 0,
        $netPremium = 0
    ) {
        try {
            $pointAssigned = false;
            $lead = Leads::where("enquiry_id", $enquiryId)->first();

            if ($lead) {

                if($lead->quote_type == "motor"){
                    $policy = CustomerPolicyModel::where(
                        "enquiry_id",
                        $enquiryId
                    )->first();

                    $case = $lead->policy_case;
                    $bussinessType = "";
                    $vehicleAge = 0;
                    if ($case == "new") {
                        $bussinessType = "New Business";
                    } else {
                        $bussinessType = "Rollover";
                    }
                    if ($bussinessType) {
                        if ($bussinessType == "Rollover") {
                            if (isset($lead->registration_date)) {
                                $regDate = $lead->registration_date;
                                $regDate = Carbon::parse($regDate);
                                $currentDate = Carbon::now();
                                $age = $currentDate->diffInYears($regDate);
                                $vehicleAge = $age . " Year";
                            }
                        }
                    }

                    if ($lead->vehicle_type == "bike") {
                        $vehicleType = "2 Wheeler";
                    } elseif ($lead->vehicle_type == "car") {
                        $vehicleType = "4 Wheeler";
                    } elseif ($lead->vehicle_type == "pcv") {
                        $vehicleType = "Commercial PCV";
                    } elseif ($lead->vehicle_type == "gcv") {
                        $vehicleType = "Commercial GCV";
                    } elseif ($lead->vehicle_type == "mcv") {
                        $vehicleType = "Misc D";
                    }

                    $modelId = $lead->model_id ?? null;

                    $rto = Rto::select("id", "rtoCode", "state")
                        ->where("rtoCode", $lead->rto_code)
                        ->first();
                    $insuranceProvider = InsuranceProvider::select(
                        "id",
                        "provider_name"
                    )
                        ->where(
                            "short_name",
                            "like",
                            "%" . $policy->insurance_provider . "%"
                        )
                        ->first();
                    $stateId = StateNew::select("id", "state_name")
                        ->where("state_name", $rto->state)
                        ->first();

                    $getMmv = self::getMmvById($lead->object_id);

                    $vehicleBody =
                        isset($getMmv) &&
                        isset($getMmv["data"]) &&
                        isset($getMmv["data"]["body_type"])
                            ? $getMmv["data"]["body_type"]
                            : "";
                    $vehicle =
                        isset($getMmv) &&
                        isset($getMmv["data"]) &&
                        isset($getMmv["data"]["vehicle_type"])
                            ? $getMmv["data"]["vehicle_type"]
                            : "";
                    $fuelType =
                        isset($getMmv) &&
                        isset($getMmv["data"]) &&
                        isset($getMmv["data"]["fuel_type"])
                            ? $getMmv["data"]["fuel_type"]
                            : "";

                    $vehicleCategory = app('App\\Helpers\\Helpers')::getVehicleCategory(
                        $vehicleType,
                        $vehicleBody,
                        $vehicle,
                        $fuelType
                    );

                    $data["agent_id"] = $lead->agent_id;
                    $data["provider"] = $insuranceProvider->id ?? null;
                    $data["vehicle_type"] = $vehicleType;
                    $data["vehicle_category"] = $vehicleCategory;
                    $data["vehicle_age"] = $vehicleAge;
                    $data["make_id"] = (string) $lead->make_id;
                    $data["state_id"] = (string) $stateId->id;
                    $data["rto_id"] = (string) $rto->id;
                    $data["cc"] = "";
                    $data["gvw"] = "";
                    $data["pcv"] = "";

                    if ($vehicleType == "2 Wheeler" || $vehicleType == "4 Wheeler") {
                        $data["cc"] =
                            getVehicleCapacity(
                                $vehicleType,
                                "cc",
                                $getMmv["data"]["providers"][0]["cc"]
                            ) ?? 0;
                    }
                    if ($vehicleType == "Commercial GCV") {
                        $data["gvw"] =
                            getVehicleCapacity(
                                $vehicleType,
                                "gvw",
                                $getMmv["data"]["Utils"][0]["gvw"]
                            ) ?? 0;
                    }
                    if ($vehicleType == "Commercial PCV") {
                        $data["pcv"] =
                            getVehicleCapacity(
                                $vehicleType,
                                "pcv",
                                $getMmv["data"]["providers"][0]["seating_capacity"]
                            ) ?? 0;
                    }
                    $data["policy_type"] =
                        $lead->product_code == "saod" ? "od" : $lead->product_code;
                    $data["business_type"] = $bussinessType;
                    $data["fuel_type"] = ucfirst($lead->fuel_type);

                    $state_id = $data["state_id"];
                    $rto_id = $data["rto_id"];
                    $make_id = $data["make_id"];
                    $fuel_type = $data["fuel_type"];

                    $pcv = $data["pcv"];
                    $gvw = $data["gvw"];
                    $cc = $data["cc"];

                    $rules = GridRule::join(
                            "agent_payouts",
                            "grid_rules.id",
                            "=",
                            "agent_payouts.rule_id"
                        )
                        ->select(
                            "grid_rules.id",
                            "agent_payouts.tp_payout_first_year",
                            "commission",
                            "od_comm",
                            "tp_comm",
                            "net_comm",
                            "commission_type",
                            "agent_payouts.payout",
                            "agent_payouts.od_payout",
                            "agent_payouts.tp_payout",
                            "agent_payouts.net_payout",
                            "effective_start_date"
                        )
                        ->where("agent_payouts.agent_id", $data["agent_id"])
                        ->where("bussiness_type", $data["business_type"])
                        ->where("provider_id", $data["provider"])
                        ->where(function ($query) use ($state_id) {
                            if ($state_id) {
                                $query
                                    ->whereJsonContains("state_id", $state_id)
                                    ->orWhereJsonContains("state_id", "select_all");
                            }
                        })
                        ->where(function ($query) use ($rto_id) {
                            if ($rto_id) {
                                $query
                                    ->whereJsonContains("rto_id", $rto_id)
                                    ->orWhereJsonContains("rto_id", "select_all");
                            }
                        })
                        ->where(function ($query) use ($make_id) {
                            $query->orWhereJsonContains("make_id", $make_id);
                        })
                        ->whereJsonContains("fuel_type", $data["fuel_type"])
                        ->where(function ($query) use ($vehicleAge, $data) {
                            if ($data["business_type"] == "New Business") {
                                $query->where("vehicle_age", "0 Year");
                            } else {
                                $query->whereRaw(
                                    '? BETWEEN CAST(SUBSTRING_INDEX(vehicle_age, "-", 1) AS UNSIGNED) AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(vehicle_age, " ", 1), "-", -1) AS UNSIGNED)',
                                    [$vehicleAge]
                                );
                            }
                        })
                        ->where("vehicle_type", $data["vehicle_type"])
                        ->whereJsonContains("vehicle_category", $data["vehicle_category"])
                        ->where("policy_type", $data["policy_type"])
                        ->when($cc, function ($query, $cc) {
                            return $query->whereJsonContains("cubic_capacity", $cc);
                        })
                        ->when($gvw, function ($query, $gvw) {
                            return $query->whereJsonContains("gvw", $gvw);
                        })
                        ->when($pcv, function ($query, $pcv) {
                            return $query->whereJsonContains("pcv", $pcv);
                        })
                        ->orderByDesc("effective_start_date")
                        ->first();

                    if ($rules) {
                        $pointAssigned = $rules->payout > 0;
                    }
                }
            }
            return $pointAssigned;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function mmv_api_call($apiUrl, $method)
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $responseArray = json_decode($response, true);
            if ($httpCode == 200) {
                return [
                    "status" => true,
                    "data" => $responseArray["data"],
                    "message" => "Success",
                ];
            } else {
                return [
                    "status" => false,
                    "data" => [],
                    "message" => $responseArray["message"] ?? 'Unknown error',
                ];
            }
        } catch (Exception $e) {
            return [
                "status" => false,
                "data" => [],
                "message" => $e->getMessage(),
            ];
        }
    }

    public static function getMmvById($objectId)
    {
        $environment = config("app.env", "production");

        $apiUrl =
            $environment === "production"
                ? config("constant.mmv_base_url.prod_url")
                : config("constant.mmv_base_url.dev_url");

        // Build the API URL with query parameters
        $apiUrl .= "get-mmv-by-id?id=" . $objectId;

        // Call the API
        $response = self::mmv_api_call($apiUrl, "GET");

        return $response;
    }
} 