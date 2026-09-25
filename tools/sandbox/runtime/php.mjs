#!/usr/bin/env node
/**
 * `php` CLI shim for the LoanPro sandbox (see runtime.mjs for the why).
 *
 *   node php.mjs -v
 *   node php.mjs artisan migrate --seed
 *   node php.mjs composer.phar install
 *
 * Arguments are forwarded verbatim; stdout/stderr are streamed and the PHP
 * exit code becomes the process exit code, so shell scripts and PHP tooling
 * (Composer, Artisan, PHPUnit) behave exactly as they would with a native PHP.
 */
import { bootPhp } from './runtime.mjs';

const args = process.argv.slice(2);

// `php` must be argv[0] for the wasm CLI SAPI to accept the args array.
const argv = ['php', ...args];

const php = await bootPhp({ extensions: [] });

let exitCode = 0;
try {
    const response = await php.cli(argv, {
        cwd: process.cwd(),
        env: Object.fromEntries(
            Object.entries(process.env).filter(([, v]) => typeof v === 'string')
        ),
    });

    for await (const chunk of response.stdout) {
        process.stdout.write(chunk);
    }
    for await (const chunk of response.stderr) {
        process.stderr.write(chunk);
    }
    exitCode = await response.exitCode;
} catch (error) {
    process.stderr.write(String(error?.stack || error) + '\n');
    exitCode = 1;
}

// Discard the wasm instance: the CLI SAPI tears its state down on exit().
try {
    php[Symbol.dispose]?.();
} catch {
    /* ignore */
}

process.exit(typeof exitCode === 'number' ? exitCode : 0);
