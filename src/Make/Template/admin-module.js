import './page/{{MODULE_ID}}-list';
import './page/{{MODULE_ID}}-detail';

const { Module } = Shopware;

Module.register('{{MODULE_ID}}', {
    type: 'plugin',
    name: '{{MODULE_ID}}',
    title: '{{MODULE_NAME}}',
    description: '{{MODULE_NAME}}',
    color: '{{COLOR}}',
    icon: 'default-basic-stack-line',

    routes: {
        list: {
            component: '{{MODULE_ID}}-list',
            path: 'list'
        },
        detail: {
            component: '{{MODULE_ID}}-detail',
            path: 'detail/:id?',
            meta: {
                parentPath: '{{MODULE_ID}}.list'
            }
        }
    },

    navigation: [{
        label: '{{MODULE_NAME}}',
        color: '{{COLOR}}',
        path: '{{MODULE_ID}}.list',
        icon: 'default-basic-stack-line',
        parent: '{{PARENT}}',
        position: 100
    }]
});
