<?php

use PHPMailer\PHPMailer\Exception;
use App\Models\GeneralSetting;
use App\Models\SocialNetwork;

// Get General Settings

if(!function_exists('get_settings')) {
    function get_settings(){
        $result = null;
        $settings = new GeneralSetting();
        $setting_data = $settings->first();

        if($setting_data) {
            $result = $setting_data;
        }else {
            $settings->insert([
                'site_name'=>'Laravecom',
                'site_email'=>'info@laravecom.test'
            ]);
            $new_settings_data = $settings->first();
            $result = $new_settings_data;
        }
        return $result;
    }
}

//Get Social Networks
if(!function_exists('get_social_network')) {
    function get_social_network() {
        $results = null;
        $social_network = new SocialNetwork();
        $social_network_data = $social_network->first();

        if($social_network_data) {
            $results = $social_network_data;
        }else {
            $social_network->insert([
                'facebook_url'=>null,
                'twitter_url'=>null,
                'instagram_url'=>null,
                'youtube_url'=>null,
                'github_url'=>null,
                'linkedin_url'=>null
            ]);
            $new_social_network_data = $social_network->first();
            $results = $new_social_network_data;
        }
        return $results;
    }
}
