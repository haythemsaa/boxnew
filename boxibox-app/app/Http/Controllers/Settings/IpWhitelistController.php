<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\IpWhitelistingService;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IpWhitelistController extends Controller
{
    protected IpWhitelistingService $ipWhitelistService;
    protected SecurityAuditService $auditService;

    public function __construct(
        IpWhitelistingService $ipWhitelistService,
        SecurityAuditService $auditService
    ) {
        $this->ipWhitelistService = $ipWhitelistService;
        $this->auditService = $auditService;
    }

    /**
     * Show IP whitelist settings page.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $whitelistedIps = $this->ipWhitelistService->getWhitelistedIps($user);
        $currentIp = $this->ipWhitelistService->getClientIp();

        return Inertia::render('Settings/IpWhitelist', [
            'whitelistedIps' => $whitelistedIps,
            'currentIp' => $currentIp,
            'isEnabled' => !empty($whitelistedIps),
        ]);
    }

    /**
     * Enable IP whitelisting.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();
        $currentIp = $this->ipWhitelistService->getClientIp();

        $this->ipWhitelistService->enableWhitelisting($user, $currentIp);

        $this->auditService->logSecurityEvent(
            'ip_whitelist_enabled',
            'security_settings',
            'success',
            ['ip' => $currentIp]
        );

        return redirect()->route('settings.ip-whitelist')
            ->with('success', 'IP whitelisting has been enabled. Your current IP has been whitelisted.');
    }

    /**
     * Disable IP whitelisting.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();
        $this->ipWhitelistService->disableWhitelisting($user);

        $this->auditService->logSecurityEvent(
            'ip_whitelist_disabled',
            'security_settings',
            'success'
        );

        return redirect()->route('settings.ip-whitelist')
            ->with('success', 'IP whitelisting has been disabled.');
    }

    /**
     * Add IP to whitelist.
     */
    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();
        $ipAddress = $request->ip_address;

        // Check if already whitelisted
        $whitelistedIps = $this->ipWhitelistService->getWhitelistedIps($user);
        if (in_array($ipAddress, $whitelistedIps)) {
            return back()->with('warning', 'This IP is already whitelisted.');
        }

        $this->ipWhitelistService->addIpToWhitelist($user, $ipAddress);

        $this->auditService->logSecurityEvent(
            'ip_added_to_whitelist',
            'security_settings',
            'success',
            ['ip' => $ipAddress]
        );

        return redirect()->route('settings.ip-whitelist')
            ->with('success', "IP address {$ipAddress} has been added to whitelist.");
    }

    /**
     * Remove IP from whitelist.
     */
    public function remove(Request $request): RedirectResponse
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();
        $ipAddress = $request->ip_address;

        $this->ipWhitelistService->removeIpFromWhitelist($user, $ipAddress);

        $this->auditService->logSecurityEvent(
            'ip_removed_from_whitelist',
            'security_settings',
            'success',
            ['ip' => $ipAddress]
        );

        return redirect()->route('settings.ip-whitelist')
            ->with('success', "IP address {$ipAddress} has been removed from whitelist.");
    }
}
