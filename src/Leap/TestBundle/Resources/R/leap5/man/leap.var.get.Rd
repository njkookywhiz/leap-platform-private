\name{leap.var.get}
\alias{leap.var.get}
\alias{c.get}
\title{
    Global variables
}
\description{
    Gets global variable value
}
\usage{
    leap.var.get(name)
}
\arguments{
    \item{name}{
        Variable name
    }
    \item{global}{
        Logical: TRUE to look for value in top most position
    }
    \item{all}{
        Logical: TRUE if to return list of all variables at specified position
    }
    \item{flowIndexOffset}{
        Numerical: flow index offset
    }
    \item{flowIndex}{
        Numerical: flow index
    }
}
\details{
}
\value{
    Value of global variable or NULL if not set
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    score <- leap.var.get("score")
}

\keyword{variable}
