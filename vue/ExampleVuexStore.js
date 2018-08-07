//import dependent libraries
import Vue from 'vue';
import Vuex from 'vuex';

//import base store
import JabberBaseStore from './../JabberBaseStore';

//import modules
import mapData from 'jabber-vuex-store-modules/mapData';
import userLocation from 'jabber-vuex-store-modules/userLocation';
import biffyStores from 'jabber-vuex-store-modules/biffyStores';
import maxMind from 'jabber-vuex-store-modules/maxMind';

//import root state files
import * as actions from './store/actions';
import * as mutations from './store/mutations';
import * as getters from './store/getters';

Vue.use(Vuex);

//create root store instance
export const store = new Vuex.Store(Object.assign(
    {},
    {
        state: {...JabberBaseStore.state, ...{
            cardsInitialized: false
        }},
        actions: {...JabberBaseStore.actions, ...actions},
        mutations: {...JabberBaseStore.mutations, ...mutations},
        getters: {...JabberBaseStore.getters, ...getters}
    },
    { modules: { userLocation, biffyStores, mapData, maxMind } }
));