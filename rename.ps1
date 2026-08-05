$pluginDir = "c:\Users\Pro\Local Sites\devlop-plugin\app\public\wp-content\plugins\wp-pixel-tracker-with-ss"

$replacements = @(
    @{ regex = 'WP Pixel Tracker With SS'; replacement = 'PixelOnWP' },
    @{ regex = 'WP Pixel Tracker with SS'; replacement = 'PixelOnWP' },
    @{ regex = 'WP Pixel Tracker'; replacement = 'PixelOnWP' },
    @{ regex = 'wp_pixel_tracker_with_ss'; replacement = 'pixel_on_wp' },
    @{ regex = 'wp-pixel-tracker-with-ss'; replacement = 'pixel-on-wp' },
    @{ regex = 'WP_Pixel_Tracker_With_Ss'; replacement = 'PixelOnWP_' },
    @{ regex = 'WP_Pixel_Tracker_'; replacement = 'PixelOnWP_' },
    @{ regex = 'wp_pixel_tracker_'; replacement = 'pixelonwp_' },
    @{ regex = 'wp-pixel-tracker'; replacement = 'pixel-on-wp' },
    @{ regex = 'WpPixelTrackerWithSs'; replacement = 'PixelOnWP' },
    @{ regex = 'PixelPulse'; replacement = 'PixelOnWP' },
    @{ regex = 'pixelpulse_'; replacement = 'pixelonwp_' },
    @{ regex = 'pixelpulse'; replacement = 'pixelonwp' },
    @{ regex = 'pixel_pulse'; replacement = 'pixel_on_wp' },
    @{ regex = 'wpt/v1'; replacement = 'pixelonwp/v1' },
    @{ regex = 'wpt_'; replacement = 'pixelonwp_' },
    @{ regex = 'WPT_'; replacement = 'PIXELONWP_' }
)

Get-ChildItem -Path $pluginDir -Recurse -File | Where-Object { $_.Extension -match '\.(php|js|css|txt|md|html)$' } | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $original = $content
    
    foreach ($r in $replacements) {
        $content = $content -replace $r.regex, $r.replacement
    }
    
    if ($content -cne $original) {
        Set-Content -Path $_.FullName -Value $content -NoNewline
        Write-Host "Updated: $($_.FullName)"
    }
}
Write-Host "Replacement complete."
