# 7) Use vuex-smart-modules in Data Bridge {#adr_0007}

Date: 2020-02-06

## Status

accepted

## Context

The Wikidata Bridge app uses VueJs version 2 and Vuex version 3 for state management.
Both within the store and in vue components the calls to `dispatch()` and `commit()` are not type safe.
That means that they accept any arguments and TypeScript will still compile without error.
Since `dispatch()` and `commit()` are typical seams that are usually mocked during unit testing,
it is up to integration, end-to-end and browser tests to detect these errors.

This is particularly unfortunate as the store is one of the central locations where business logic happens.

We considered two options:
1. writing our own set of wrappers for `dispatch()` and `commit()` to get type safety
1. using vuex-smart-modules

The advantages of doing it ourselves included us not having another dependency.
The risks include that this would be yet another homebrew layer of abstraction.

The advantages of using vuex-smart-modules include:
- it gives us proper native type safety both in the store and in components
- we can call actions/getters/mutations with [method style access](https://github.com/ktsn/vuex-smart-module#method-style-access-for-actions-and-mutations)
  - we can get rid of all the `BRIDGE_SET_TARGET_VALUE` constants without having to fall back to string literals
  - we can actually use the IDE's `go to method definition` functionality
  - we can rely on the action's return type instead of dispatch's `Promise<any>` being used everywhere
- we can drop the `vuex-class` dependency as we can use vuex-smart-modules for all store access in components
- it is developed by a VueJs core contributor

The risks include:
- it is still a very new project with a 0.x.y version number
- it is another layer on top of vuex, which means the documentation may not be as good as it could be
- we are the first big project to use it
- mocking of dependencies and nested modules in testing still seems to be not handled as diligently as one would wish

## Decision

We decided to rewrite our Vuex store using vuex-smart-modules version 0.3.4

## Consequences

### Expected Consequences
#### We expect that this will give us the following benefits:
- `dispatch()` and `commit()` in the store will be type safe
- We can create "module contexts" in the components and use dispatch in type safe way there as well
- We should be able to use [method style access](https://github.com/ktsn/vuex-smart-module#method-style-access-for-actions-and-mutations)
and thus avoid using `this.dispatch()` which returns `Promise<any>` and use our actions directly and thus fully benefit from their return types.

#### We expect that the following things will be harder:
- The central `Module` constructor of vuex-smart-modules consumes the class definitions of, for example, `RootActions` themselves.
Therefore, it is not possible to use the `RootActions` constructor for dependency injection
  - These dependencies have to be injected using a special lifecycle hook
  - This injection cannot be hijacked in tests, therefore we have to overwrite private properties in jest using `// @ts-ignore`
- We can nest modules only 1 level deep
- We are the first big project to use vuex-smart-modules.
This means we still have to figure out a lot of things for the first time.

### Learnings

*Future learnings should be amended here*
