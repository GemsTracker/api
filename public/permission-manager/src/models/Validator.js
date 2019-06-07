export default class Validator
{
    constructor(structure, idField=null) {
        //this.model = model;

        this.errors = null;
        this.structure = structure;
        this.idField = idField;
        
        console.log('STRUCTURE!');
        console.log(this.structure);
    }

    /*async getStructure()
    {
        if (this.structure === null) {
            this.structure = await this.model.structure();
        }
        return this.structure;
    }*/

    getValidators()
    {
        this.errors = null;
        const structure = this.structure;

        let modelValidators = {};
        for(const variable in structure) {

            if (variable == this.idField) {
                continue;
            }
            const variableSettings = structure[variable];
            let validators = {};
            if (variableSettings.hasOwnProperty('required') && variableSettings.required === true) {
                validators.required = true;
            }
            
            if (variableSettings.hasOwnProperty('type')) {
                let value = {};
                switch (variableSettings.type) {
                    case 'numeric': 
                        if (variableSettings.hasOwnProperty('min')) {
                            value.min = variableSettings.min;
                        }
                        if (variableSettings.hasOwnProperty('max')) {
                            value.max = variableSettings.max;
                        }
                        if (Object.keys(value).length === 0) {
                            value = true;
                        }
                        validators.numeric = value;
                        break;
                    case 'string':
                        if (variableSettings.hasOwnProperty('maxlength')) {
                            value.size = variableSettings.maxlength;
                        }
                        if (Object.keys(value).length === 0) {
                            value = true;
                        }
                        validators.string = value;
                        break;
                }
            }

            if (Object.keys(validators).length > 0) {
                modelValidators[variable] = validators;
            }            
        }

        return modelValidators;
    }

    validate(row)
    {
        const modelValidators = this.getValidators();

        let errors = {};

        for (const variable in modelValidators) {
            const validators = modelValidators[variable];
            let variableErrors = {};
            for (const validator in validators) {
                if (validator === 'required') {
                    if (!row.hasOwnProperty(variable) || row[variable] === null || row[variable] === '') {
                        variableErrors.required = 'Value can\'t be empty.';
                        break;
                    }
                }
                
                if (row.hasOwnProperty(variable) && row[variable] !== null) {
                    const value = row[variable];
                    
                    if (validator === 'numeric') {
                        if (Number(parseFloat(value)) !== value) {
                            variableErrors.notANumber = 'Value should be a number';
                        } else if (typeof validators[validator] == 'object') {
                            if (validators[validator].hasOwnProperty('min') && value < validators[validator].min) {
                                variableErrors.smallerThan = 'Value can\'t be smaller than ' + validators[validator].min + '.';
                            }
                            if (validators[validator].hasOwnProperty('max') && value > validators[validator].max) {
                                variableErrors.greaterThan = 'Value can\'t be bigger than ' + validators[validator].max + '.';
                            }
                        }
                    }
                    if (validator === 'string') {
                        if (typeof value !== 'string') {
                            variableErrors.notAString = 'Value is not a string';
                        } else if (typeof validators[validator] == 'object') {
                            if (validators[validator].hasOwnProperty('maxlength') && value.length > validators[validator].maxlength) {
                                variableErrors.tooManyChars = 'Value can\'t be longer than ' + validators[validator].maxlength + '.';
                            }
                        }
                    }
                }
            }
            if (Object.keys(variableErrors).length > 0) {
                errors[variable] = variableErrors;
            }
        }

        console.log('ERRORS!');
        console.log(row);
        console.log(errors);

        if (Object.keys(errors).length > 0) {
            this.errors = errors;
            return false;
        }
        return true;
    }
}