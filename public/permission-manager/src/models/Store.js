import store from './../store';

class Store {
	constructor(name)
	{
		this.storeId = name
	}

	register({state, mutations, actions, getters} = {})
	{
		if (this.state) {
			//this.destroy();
		} else {
			store.registerModule(this.storeId.split('/'), {
				namespaced: true,
				state,
				mutations,
				actions,
				getters
			});
		}
	}

	destroy()
	{
		store.unregisterModule(this.storeId.split('/'));
	}

	dispatch(action, data)
	{
		return store.dispatch(`${this.storeId}/${action}`, data);
	}

	commit(mutation, data)
	{
		return store.commit(`${this.storeId}/${mutation}`, data);
	}

	getters(getter)
	{
		return store.getters[`${this.storeId}/${getter}`];
	}

	get store()
	{
		return store;
	}

	get state()
	{
		let state = store.state;
		this.storeId.split('/').forEach(part => {
			state = state[part];
		})
		return state;
	}
}

export default Store;