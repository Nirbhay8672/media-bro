<?php

namespace App\Services;

use App\Models\Template;
use App\Models\TemplateVisit;
use App\Models\TemplateDownload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VisitorTrackingService
{
    public function trackVisit(Template $template, Request $request): void
    {
        try {
            // Check if the visits table exists
            if (!\Schema::hasTable('template_visits')) {
                Log::warning('Template visits table does not exist. Skipping visit tracking.');
                return;
            }

            $ipAddress = $this->getClientIpAddress($request);
            
            // Check if this IP has already visited this template
            $existingVisit = TemplateVisit::where('template_id', $template->id)
                ->where('ip_address', $ipAddress)
                ->first();

            if ($existingVisit) {
                return; // Don't track duplicate visits
            }

            $userAgent = $request->userAgent();
            $username = $this->getUsernameFromRequest($request);
            $locationData = $this->getLocationFromIp($ipAddress);

            TemplateVisit::create([
                'template_id' => $template->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'username' => $username,
                'country' => $locationData['country'] ?? null,
                'city' => $locationData['city'] ?? null,
                'region' => $locationData['region'] ?? null,
                'timezone' => $locationData['timezone'] ?? null,
                'visited_at' => now(),
            ]);

            Log::info('Template visit tracked successfully', [
                'template_id' => $template->id,
                'ip_address' => $ipAddress
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track template visit', [
                'template_id' => $template->id,
                'ip_address' => $request->ip(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function trackDownload(Template $template, Request $request, string $fileName, ?int $fileSize = null): void
    {
        try {
            // Check if the downloads table exists
            if (!\Schema::hasTable('template_downloads')) {
                Log::warning('Template downloads table does not exist. Skipping download tracking.');
                return;
            }

            $ipAddress = $this->getClientIpAddress($request);
            
            // Check if this IP has already downloaded this template
            $existingDownload = TemplateDownload::where('template_id', $template->id)
                ->where('ip_address', $ipAddress)
                ->first();

            if ($existingDownload) {
                return; // Don't track duplicate downloads
            }

            $userAgent = $request->userAgent();
            $username = $this->getUsernameFromRequest($request);
            $locationData = $this->getLocationFromIp($ipAddress);

            TemplateDownload::create([
                'template_id' => $template->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'username' => $username,
                'country' => $locationData['country'] ?? null,
                'city' => $locationData['city'] ?? null,
                'region' => $locationData['region'] ?? null,
                'timezone' => $locationData['timezone'] ?? null,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'downloaded_at' => now(),
            ]);

            Log::info('Template download tracked successfully', [
                'template_id' => $template->id,
                'ip_address' => $ipAddress,
                'file_name' => $fileName
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track template download', [
                'template_id' => $template->id,
                'ip_address' => $this->getClientIpAddress($request),
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getClientIpAddress(Request $request): string
    {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancers/proxies
            'HTTP_X_FORWARDED',          // Load balancers/proxies
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxies
            'HTTP_FORWARDED',            // Proxies
            'REMOTE_ADDR'                // Standard
        ];

        $fullIp = null;
        foreach ($headers as $header) {
            if ($request->server($header)) {
                $ips = explode(',', $request->server($header));
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $fullIp = $ip;
                    break;
                }
            }
        }

        if (!$fullIp) {
            $fullIp = $request->ip() ?? '127.0.0.1';
        }

        // Format IP to first 3 octets (152.26.25 format)
        return $this->formatIpToThreeOctets($fullIp);
    }

    private function formatIpToThreeOctets(string $ipAddress): string
    {
        // Split IP address into octets
        $octets = explode('.', $ipAddress);
        
        // If it's a valid IPv4 address with 4 octets, return first 3
        if (count($octets) === 4 && filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $octets[0] . '.' . $octets[1] . '.' . $octets[2];
        }
        
        // For IPv6 or invalid IPs, return a default format
        return '127.0.0';
    }

    private function getUsernameFromRequest(Request $request): ?string
    {
        // Check if user is authenticated
        if ($request->user()) {
            return $request->user()->username ?? $request->user()->name;
        }

        // Check for username in query parameters or headers
        $username = $request->query('username') ?? $request->header('X-Username');
        
        return $username ?: null;
    }

    private function getLocationFromIp(string $ipAddress): array
    {
        // Skip local IPs and 3-octet format (can't get location for partial IP)
        if ($this->isLocalIp($ipAddress) || $this->isThreeOctetFormat($ipAddress)) {
            return [];
        }

        try {
            // Using ipapi.co (free tier: 1000 requests/day)
            $response = file_get_contents("http://ipapi.co/{$ipAddress}/json/");
            $data = json_decode($response, true);

            if ($data && !isset($data['error'])) {
                return [
                    'country' => $data['country_name'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get location data for IP', [
                'ip_address' => $ipAddress,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    private function isLocalIp(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function isThreeOctetFormat(string $ipAddress): bool
    {
        // Check if IP is in 3-octet format (e.g., 152.26.25)
        $octets = explode('.', $ipAddress);
        return count($octets) === 3;
    }
}

