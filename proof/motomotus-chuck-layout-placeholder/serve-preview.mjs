import { createServer } from 'node:http';
import { promises as fs } from 'node:fs';
import { dirname, extname, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)));
const host = '127.0.0.1';
const port = Number(process.argv[2] || 4193);
const contentTypes = { '.css': 'text/css; charset=utf-8', '.html': 'text/html; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.mjs': 'text/javascript; charset=utf-8' };
const headers = { 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff' };

const server = createServer(async (request, response) => {
  if (!['GET', 'HEAD'].includes(request.method)) {
    response.writeHead(405, { ...headers, Allow: 'GET, HEAD', 'Content-Type': 'text/plain; charset=utf-8' });
    response.end('Method Not Allowed');
    return;
  }
  let pathname;
  try { pathname = decodeURIComponent(new URL(request.url || '/', `http://${host}`).pathname); } catch { pathname = null; }
  const filePath = pathname ? resolve(root, `.${pathname === '/' ? '/index.html' : pathname}`) : null;
  if (!filePath || (filePath !== root && !filePath.startsWith(`${root}${sep}`))) {
    response.writeHead(400, { ...headers, 'Content-Type': 'text/plain; charset=utf-8' });
    response.end('Bad Request');
    return;
  }
  let stat;
  try { stat = await fs.stat(filePath); } catch { stat = null; }
  if (!stat?.isFile()) {
    response.writeHead(404, { ...headers, 'Content-Type': 'text/plain; charset=utf-8' });
    response.end('Not Found');
    return;
  }
  response.writeHead(200, { ...headers, 'Content-Length': stat.size, 'Content-Type': contentTypes[extname(filePath)] || 'application/octet-stream' });
  if (request.method === 'HEAD') response.end();
  else response.end(await fs.readFile(filePath));
});

server.listen(port, host, () => console.log(`Layout placeholder preview listening on http://${host}:${port}/`));
