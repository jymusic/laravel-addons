
var drinkbar = require('gulp-drinkbar')

drinkbar
	.task('scripts:{$addon_name}')
	.directory(__dirname)
	.scripts({
		inputs: [
		],
		output: 'root:public/{$addon_name}.js',
	})
