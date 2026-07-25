import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const packageRoot = process.env.PLAYGROUND_NODE_MODULES;
if (!packageRoot) {
	throw new Error('PLAYGROUND_NODE_MODULES is required.');
}

const { PHP } = await import(path.join(packageRoot, '@php-wasm/universal/index.js'));
const { loadNodeRuntime } = await import(path.join(packageRoot, '@php-wasm/node/index.js'));
const phpVersion = process.env.PHP_VERSION || '8.3';
const php = new PHP(
	await loadNodeRuntime(phpVersion, {
		emscriptenOptions: { processId: 1 },
	})
);
const target = path.resolve(process.argv[2] || 'docsbot');
const files = [];

function visit(directory) {
	for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
		const fullPath = path.join(directory, entry.name);
		if (entry.isDirectory()) {
			visit(fullPath);
		} else if (entry.isFile() && entry.name.endsWith('.php')) {
			files.push(fullPath);
		}
	}
}

visit(target);
let failed = false;

for (const file of files.sort()) {
	const source = fs.readFileSync(file).toString('base64');
	const result = await php.runStream({
		code: `<?php
try {
	token_get_all(base64_decode('${source}'), TOKEN_PARSE);
	echo "OK";
} catch (ParseError $error) {
	fwrite(STDERR, $error->getMessage());
	exit(1);
}`,
	});
	const stdout = await result.stdoutText;
	const stderr = await result.stderrText;
	if ((typeof result.exitCode === 'number' && result.exitCode !== 0) || stdout.trim() !== 'OK') {
		failed = true;
		console.error(`ERROR ${path.relative(process.cwd(), file)}: ${stderr || stdout}`);
	} else {
		console.log(`OK ${path.relative(process.cwd(), file)}`);
	}
}

process.exit(failed ? 1 : 0);
