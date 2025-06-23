<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PhonePeBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the Authorization header from the request
        $authorization = $request->header('Authorization');

        // Log the incoming request
        Log::channel('phonepe_webhook')->info('PhonePe Callback Authorization Check Started', [
            'authorization_received' => $authorization ?? 'None',
            'request_ip'             => $request->ip(),
            'request_data'           => $request->all(),
        ]);

        if (! $authorization) {
            Log::channel('phonepe_webhook')->warning('Authorization Header Missing');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Compute SHA256 hash of "username:password"
        $username     = "manish_jwellers_2025";
        $password     = "KoncptAI321";
        $computedHash = hash('sha256', $username . ':' . $password);

        // Log computed hash details
        Log::channel('phonepe_webhook')->info('Computed Hash for Authorization', [
            'username'      => $username,
            'password'      => $password,
            'computed_hash' => $computedHash,
        ]);

        // Compare the computed hash with the received Authorization header
        if (! hash_equals($computedHash, $authorization)) {
            Log::channel('phonepe_webhook')->warning('PhonePe Callback Authorization Mismatch', [
                'expected_hash' => $computedHash,
                'received_hash' => $authorization,
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Authorization successful
        Log::channel('phonepe_webhook')->info('PhonePe Callback Authorized Successfully');

        // Proceed with the request
        return $next($request);
    }

}
