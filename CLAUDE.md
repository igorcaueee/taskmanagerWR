# Instruções para o Claude

## Fluxo de Commit

Sempre que eu pedir para commitar, salvar, fazer push ou qualquer variação disso, execute o fluxo abaixo **sem pular etapas**.

### Antes de commitar

Inspecione o repositório para entender o padrão existente:

```bash
git branch -a
git log --oneline -15
git status
git diff --stat
```

Use o histórico para definir:
- **Nome da branch**: seguindo o padrão das branches anteriores do projeto
- **Mensagem do commit**: seguindo o padrão dos commits anteriores do projeto

### Fluxo completo

```bash
git checkout master
git add .
git checkout -b "<branch-name>"
git commit -m "<mensagem-do-commit>"
git push origin <branch-name>
git checkout master
git merge <branch-name>
git push origin master
```

### Ao finalizar, me informe:

```
✅ Commit realizado!

Branch: <branch-name>
Commit: <mensagem>
Merge na master: feito
Push origin master: feito

Lembre de fazer o git pull no servidor!
```

### Regras

- Nunca fazer `git pull` no servidor — isso é responsabilidade minha
- Se o push falhar por upstream não configurado, usar: `git push --set-upstream origin <branch-name>`
- Se houver conflito no merge, me avisar e não tentar resolver sozinho
- Sempre voltar para `master` antes de criar a nova branch
