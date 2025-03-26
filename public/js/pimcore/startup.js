pimcore.registerNS("pimcore.plugin.PimcoreKeycloakBundle");

pimcore.plugin.PimcoreKeycloakBundle = Class.create({

    initialize: function () {
        console.log("Pimcore Keycloak Bundle initialized");
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        // Получаем существующее меню из события
        let menu = e.detail.menu;

        // Добавляем новый пункт меню для Keycloak
        menu.keycloakAccount = {
            label: t('keycloak_account'),
            iconCls: 'pimcore_keycloak_icon',
            priority: 42, // приоритет для определения позиции в меню
            items: [], // элементы подменю (в данном случае их нет)
            shadow: false,
            handler: this.openKeycloakAccount,
            noSubmenus: true,
            cls: "pimcore_navigation_flyout"
        };
    },

    // Обработчик для открытия аккаунта Keycloak
    openKeycloakAccount: function() {
        // Открываем управление аккаунтом Keycloak в новой вкладке
        window.open('/admin/keycloak/account', '_blank');
    }
});

var PimcoreKeycloakBundlePlugin = new pimcore.plugin.PimcoreKeycloakBundle();
