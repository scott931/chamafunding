<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeviceInfoService
{
    /**
     * Extract device information from request
     */
    public function getDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';
        
        return [
            'ip_address' => $this->getIpAddress($request),
            'user_agent' => $userAgent,
            'device_type' => $this->getDeviceType($userAgent),
            'device_name' => $this->getDeviceName($userAgent),
            'browser' => $this->getBrowser($userAgent),
            'browser_version' => $this->getBrowserVersion($userAgent),
            'os' => $this->getOS($userAgent),
            'os_version' => $this->getOSVersion($userAgent),
            'location' => $this->getLocation($request),
        ];
    }

    /**
     * Get IP address from request
     */
    private function getIpAddress(Request $request): ?string
    {
        // Check for forwarded IP first (for proxies/load balancers)
        $ip = $request->header('X-Forwarded-For');
        if ($ip) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }
        
        if (!$ip) {
            $ip = $request->header('X-Real-IP');
        }
        
        if (!$ip) {
            $ip = $request->ip();
        }
        
        // Filter out local/private IPs if needed
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
        
        return $ip;
    }

    /**
     * Determine device type (desktop, mobile, tablet)
     */
    private function getDeviceType(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        
        return 'desktop';
    }

    /**
     * Get device name
     */
    private function getDeviceName(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        // Mobile devices
        if (preg_match('/iphone/i', $userAgent)) return 'iPhone';
        if (preg_match('/ipad/i', $userAgent)) return 'iPad';
        if (preg_match('/ipod/i', $userAgent)) return 'iPod';
        if (preg_match('/android/i', $userAgent)) {
            if (preg_match('/tablet/i', $userAgent)) return 'Android Tablet';
            return 'Android Phone';
        }
        if (preg_match('/blackberry/i', $userAgent)) return 'BlackBerry';
        if (preg_match('/windows phone/i', $userAgent)) return 'Windows Phone';
        
        // Desktop
        if (preg_match('/windows/i', $userAgent)) return 'Windows PC';
        if (preg_match('/macintosh|mac os x/i', $userAgent)) return 'Mac';
        if (preg_match('/linux/i', $userAgent)) return 'Linux PC';
        
        return null;
    }

    /**
     * Get browser name
     */
    private function getBrowser(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edg|opr/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/edg/i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/opr|opera/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/msie|trident/i', $userAgent)) {
            return 'Internet Explorer';
        }
        
        return null;
    }

    /**
     * Get browser version
     */
    private function getBrowserVersion(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        // Chrome
        if (preg_match('/chrome\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }
        // Safari
        if (preg_match('/version\/([\d.]+).*safari/i', $userAgent, $matches)) {
            return $matches[1];
        }
        // Firefox
        if (preg_match('/firefox\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }
        // Edge
        if (preg_match('/edg\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }
        // Opera
        if (preg_match('/opr\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Get operating system
     */
    private function getOS(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/windows/i', $userAgent)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $userAgent)) return 'macOS';
        if (preg_match('/linux/i', $userAgent)) return 'Linux';
        if (preg_match('/android/i', $userAgent)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) return 'iOS';
        if (preg_match('/blackberry/i', $userAgent)) return 'BlackBerry OS';
        if (preg_match('/windows phone/i', $userAgent)) return 'Windows Phone';
        
        return null;
    }

    /**
     * Get OS version
     */
    private function getOSVersion(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        // Windows
        if (preg_match('/windows nt ([\d.]+)/i', $userAgent, $matches)) {
            $version = $matches[1];
            $versions = [
                '10.0' => '10',
                '6.3' => '8.1',
                '6.2' => '8',
                '6.1' => '7',
            ];
            return $versions[$version] ?? $version;
        }
        // macOS
        if (preg_match('/mac os x ([\d_]+)/i', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }
        // Android
        if (preg_match('/android ([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }
        // iOS
        if (preg_match('/os ([\d_]+)/i', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }
        
        return null;
    }

    /**
     * Get location information from IP
     */
    private function getLocation(Request $request): array
    {
        $ip = $this->getIpAddress($request);
        
        if (!$ip || $this->isLocalIp($ip)) {
            return [
                'country' => null,
                'city' => null,
                'region' => null,
                'latitude' => null,
                'longitude' => null,
            ];
        }
        
        try {
            // Use ip-api.com (free, no API key required, 45 requests/minute)
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon");
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail - location is optional
            Log::debug('Failed to get location for IP: ' . $ip, ['error' => $e->getMessage()]);
        }
        
        return [
            'country' => null,
            'city' => null,
            'region' => null,
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * Check if IP is local/private
     */
    private function isLocalIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}

