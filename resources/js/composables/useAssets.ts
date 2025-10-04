import { usePage } from '@inertiajs/vue3';

export function useAssets() {
    const page = usePage();
    
    const getLogoUrl = () => {
        return page.props.logo_url as string || '/images/logo.png';
    };
    
    const getFaviconUrl = () => {
        return page.props.favicon_url as string || '/images/logo.png';
    };
    
    const getAppleTouchIconUrl = () => {
        return page.props.apple_touch_icon_url as string || '/images/logo.png';
    };
    
    const getAppUrl = () => {
        return page.props.app_url as string || window.location.origin;
    };
    
    return {
        getLogoUrl,
        getFaviconUrl,
        getAppleTouchIconUrl,
        getAppUrl,
    };
}
