/*
South African Theological Seminary
 */

const that = this;

class AddonLocalMailLinkHandler extends that.CoreContentLinksHandlerBase {
    name = 'AddonLocalMailLinkHandler';
    pattern = new RegExp('/local/satsmail/view.php');

    getActions(siteIds, url, params) {
        const action = {
            action(siteId) {
                const page = `siteplugins/content/local_satsmail/view/0`;
                const pageParams = {
                    title: 'plugin.local_satsmail.pluginname',
                    args: params,
                };
                that.CoreNavigatorService.navigateToSitePath(page, { params: pageParams, siteId });
            },
        };

        return [action];
    }
}

class AddonLocalMaiMainMenuHandler {
    name = 'AddonLocalMailMainMenuHandler';

    async isEnabled() {
        return true;
    }

    getDisplayData() {
        return {
            title: 'plugin.local_satsmail.pluginname',
            icon: 'far-envelope',
            page: 'siteplugins/content/local_satsmail/view/0',
            get pageParams() {
                const zoomLevel = document.documentElement.style.getPropertyValue('--zoom-level');
                return {
                    title: 'plugin.local_satsmail.pluginname',
                    args: {
                        appzoom: parseInt(zoomLevel) / 100,
                    },
                };
            },
        };
    }
}

that.CoreMainMenuDelegate.registerHandler(new AddonLocalMaiMainMenuHandler());
that.CoreContentLinksDelegate.registerHandler(new AddonLocalMailLinkHandler());

